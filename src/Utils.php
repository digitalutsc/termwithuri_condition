<?php

namespace Drupal\termwithuri_condition;

use Drupal\media\MediaInterface;
use Drupal\taxonomy\TermInterface;

class Utils {

  const EXTERNAL_URI_FIELD = 'field_external_uri';

  const MEDIA_OF_FIELD = 'field_media_of';

  const MEDIA_USAGE_FIELD = 'field_media_use';
  const MEMBER_OF_FIELD = 'field_member_of';
  const MODEL_FIELD = 'field_model';


  /**
   * Gets the taxonomy term associated with an external uri.
   *
   * @param string $uri
   *   External uri.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   Term or NULL if not found.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Calling getStorage() throws if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Calling getStorage() throws if the storage handler couldn't be loaded.
   */
  public static function getTermForUri($uri) {
    // Get authority link fields to search.

    $field_map = \Drupal::service('entity_field.manager')->getFieldMap();;
    $fields = [];
    foreach ($field_map['taxonomy_term'] as $field_name => $field_data) {
      if ($field_data['type'] == 'authority_link') {
        $fields[] = $field_name;
      }
    }
    // Add field_external_uri.
    $fields[] = self::EXTERNAL_URI_FIELD;

    $query = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->getQuery();

    $orGroup = $query->orConditionGroup();
    foreach ($fields as $field) {
      $orGroup->condition("$field.uri", $uri);
    }

    $results = $query
      ->condition($orGroup)
      ->execute();

    if (empty($results)) {
      return NULL;
    }

    return \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->load(reset($results));
  }

  /**
   * Gets every field name that might contain an external uri for a term.
   *
   * @return string[]
   *   Field names for fields that a term may have as an external uri.
   */
  public static function getUriFieldNamesForTerms() {
    // Get authority link fields to search.
    $field_map = \Drupal::service('entity_field.manager')->getFieldMap();
    $fields = [];
    foreach ($field_map['taxonomy_term'] as $field_name => $field_data) {
      $data_types = ['authority_link', 'field_external_authority_link'];
      if (in_array($field_data['type'], $data_types)) {
        $fields[] = $field_name;
      }
    }
    // Add field_external_uri.
    $fields[] = self::EXTERNAL_URI_FIELD;
    return $fields;
  }

  /**
   * Gets the taxonomy term associated with an external uri.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   Taxonomy term.
   *
   * @return string|null
   *   URI or NULL if not found.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   Method $field->first() throws if data structure is unset and no item can
   *   be created.
   */
  public static function getUriForTerm(TermInterface $term) {
    $fields = self::getUriFieldNamesForTerms();
    foreach ($fields as $field_name) {
      if ($term && $term->hasField($field_name)) {
        $field = $term->get($field_name);
        if (!$field->isEmpty()) {
          $link = $field->first()->getValue();
          return $link['uri'];
        }
      }
    }
    return NULL;
  }

  /**
   * Gets nodes that a media belongs to.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The Media whose node you are searching for.
   *
   * @return \Drupal\node\NodeInterface
   *   Parent node.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   Method $field->first() throws if data structure is unset and no item can
   *   be created.
   */
  public static function getParentNode(MediaInterface $media) {
    if (!$media->hasField(self::MEDIA_OF_FIELD)) {
      return NULL;
    }
    $field = $media->get(self::MEDIA_OF_FIELD);
    if ($field->isEmpty()) {
      return NULL;
    }
    $parent = $field->first()
      ->get('entity')
      ->getTarget();
    if (!is_null($parent)) {
      return $parent->getValue();
    }
    return NULL;
  }


}
