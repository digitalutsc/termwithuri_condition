<?php

namespace Drupal\termwithuri_condition\Plugin\EntityReferenceSelection;

use Drupal\taxonomy\Plugin\EntityReferenceSelection\TermSelection;
use Drupal\termwithuri_condition\Utils;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filters by looking for entities with Authority Links or External Uris.
 *
 * @EntityReferenceSelection(
 *   id = "islandora:external_uri",
 *   label = @Translation("Taxonomy Term with external URI selection"),
 *   entity_types = {"taxonomy_term"},
 *   group = "islandora",
 *   weight = 1
 * )
 */
class ExternalUriSelection extends TermSelection {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $options = parent::getReferenceableEntities($match, $match_operator, $limit);

    foreach (array_keys($options) as $vid) {
      foreach (array_keys($options[$vid]) as $tid) {
        $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
        $uri = Utils::getUriForTerm($term);
        if (empty($uri)) {
          unset($options[$vid][$tid]);
        }
      }
      if (empty($options[$vid])) {
        unset($options[$vid]);
      }
    }

    return $options;
  }

}
