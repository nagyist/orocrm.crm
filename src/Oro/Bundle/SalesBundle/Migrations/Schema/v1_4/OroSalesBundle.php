<?php

namespace Oro\Bundle\SalesBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSalesBundle implements Migration, ExtendExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->extendExtension->addManyToOneRelation(
            $schema,
            'orocrm_sales_lead',
            'campaign',
            'orocrm_campaign',
            'combined_name',
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM]]
        );
    }
}
