<?php

namespace Drupal\rapidkit_oauth\Controller;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\openid_connect\OpenIDConnectClientEntityInterface;
use Drupal\openid_connect\OpenIDConnectClaims;
use Drupal\openid_connect\Plugin\OpenIDConnectClientInterface;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for OAuth login.
 */
final class OAuthController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The OpenID Connect claims service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectClaims
   */
  protected $claims;

  /**
   * The OpenID Connect session service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectSessionInterface
   */
  protected $session;

  /**
   * Constructs a new OAuthController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\openid_connect\OpenIDConnectClaims $claims
   *   The OpenID Connect claims service.
   * @param \Drupal\openid_connect\OpenIDConnectSessionInterface $session
   *   The OpenID Connect session service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, OpenIDConnectClaims $claims, OpenIDConnectSessionInterface $session) {
    $this->entityTypeManager = $entity_type_manager;
    $this->claims = $claims;
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('openid_connect.claims'),
      $container->get('openid_connect.session')
    );
  }

  /**
   * Access callback.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(): AccessResultInterface {
    // Load the Google client entity.
    /** @var \Drupal\openid_connect\OpenIDConnectClientEntityInterface[] $clients */
    $clients = $this->entityTypeManager->getStorage('openid_connect_client')->loadByProperties(['id' => 'google']);
    if (empty($clients)) {
      // Add cache tag for the entity list so cache invalidates when
      // a client is created or the list changes.
      return AccessResult::forbidden('The OpenID Connect client does not exist.')
        ->addCacheTags(['config:openid_connect_client_list']);
    }

    $client = reset($clients);
    assert($client instanceof OpenIDConnectClientEntityInterface);

    // Add the client entity as a cacheable dependency so access cache
    // invalidates when the client is enabled/disabled or modified.
    $access_result = AccessResult::allowed()
      ->addCacheableDependency($client);

    if (!$client->status()) {
      return AccessResult::forbidden('The OpenID Connect client is disabled.')
        ->addCacheableDependency($client);
    }

    return $access_result;
  }

  /**
   * Initiates the OAuth login flow.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The redirect response to the OAuth provider.
   */
  public function login(Request $request) {
    // Load the Google client entity.
    /** @var \Drupal\openid_connect\OpenIDConnectClientEntityInterface[] $clients */
    $clients = $this->entityTypeManager->getStorage('openid_connect_client')->loadByProperties(['id' => 'google']);
    if (empty($clients)) {
      throw new NotFoundHttpException('OpenID Connect client not found.');
    }

    $client = reset($clients);
    assert($client instanceof OpenIDConnectClientEntityInterface);
    if (!$client->status()) {
      throw new NotFoundHttpException('OpenID Connect client is disabled.');
    }

    // Save the destination.
    $this->session->saveDestination();

    // Get the plugin and initiate authorization.
    try {
      /** @var \Drupal\openid_connect\Plugin\OpenIDConnectClientInterface|null $plugin */
      $plugin = $client->getPlugin();
    }
    catch (PluginException $exception) {
      throw new NotFoundHttpException('OpenID Connect client plugin not found.', $exception);
    }

    if (!$plugin instanceof OpenIDConnectClientInterface) {
      throw new NotFoundHttpException('OpenID Connect client plugin not found.');
    }

    $scopes = $this->claims->getScopes($plugin);
    $this->session->saveOp('login');

    // Redirect to the OAuth provider.
    return $plugin->authorize($scopes);
  }

}
