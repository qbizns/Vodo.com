<?php

use App\Services\Entity\EntityRegistry;
use App\Services\Taxonomy\TaxonomyRegistry;

/**
 * Entity and Taxonomy Helper Functions
 *
 * These functions provide a convenient way to work with entities and taxonomies.
 * They're defined in a separate file and loaded with require_once to avoid redeclaration.
 */

// Entity helpers
if (!function_exists('register_entity')) {
    function register_entity(string $name, array $config = [], ?string $pluginSlug = null)
    {
        return EntityRegistry::getInstance()->register($name, $config, $pluginSlug);
    }
}

if (!function_exists('get_entity')) {
    function get_entity(string $name)
    {
        return EntityRegistry::getInstance()->get($name);
    }
}

if (!function_exists('entity_exists')) {
    function entity_exists(string $name): bool
    {
        return EntityRegistry::getInstance()->exists($name);
    }
}

if (!function_exists('create_entity_record')) {
    function create_entity_record(string $entityName, array $data)
    {
        return EntityRegistry::getInstance()->createRecord($entityName, $data);
    }
}

if (!function_exists('query_entity')) {
    function query_entity(string $entityName)
    {
        return EntityRegistry::getInstance()->query($entityName);
    }
}

// Taxonomy helpers
if (!function_exists('register_taxonomy')) {
    function register_taxonomy(string $name, $entityNames, array $config = [], ?string $pluginSlug = null)
    {
        return TaxonomyRegistry::getInstance()->register($name, $entityNames, $config, $pluginSlug);
    }
}

if (!function_exists('get_taxonomy')) {
    function get_taxonomy(string $name)
    {
        return TaxonomyRegistry::getInstance()->get($name);
    }
}

if (!function_exists('get_terms')) {
    function get_terms(string $taxonomyName)
    {
        return TaxonomyRegistry::getInstance()->getTerms($taxonomyName);
    }
}

if (!function_exists('create_term')) {
    function create_term(string $taxonomyName, array $data)
    {
        return TaxonomyRegistry::getInstance()->createTerm($taxonomyName, $data);
    }
}
