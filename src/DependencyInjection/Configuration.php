<?php
/**
 * 30.01.2020.
 */

declare(strict_types=1);

namespace srr\EventSubscriber\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder()
    {
        return new TreeBuilder('event_subscriber');
    }
}