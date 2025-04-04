<?php

namespace Oro\Bundle\ContactBundle\Migrations\Schema;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroContactBundleInstaller implements
    Installation,
    DatabasePlatformAwareInterface,
    AttachmentExtensionAwareInterface,
    ActivityExtensionAwareInterface
{
    use DatabasePlatformAwareTrait;
    use AttachmentExtensionAwareTrait;
    use ActivityExtensionAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_18';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOrocrmContactTable($schema);
        $this->createOrocrmContactAddressTable($schema);
        $this->createOrocrmContactAdrToAdrTypeTable($schema);
        $this->createOrocrmContactEmailTable($schema, $queries);
        $this->createOrocrmContactGroupTable($schema);
        $this->createOrocrmContactMethodTable($schema);
        $this->createOrocrmContactPhoneTable($schema);
        $this->createOrocrmContactSourceTable($schema);
        $this->createOrocrmContactToContactGrpTable($schema);

        /** Foreign keys generation **/
        $this->addOrocrmContactForeignKeys($schema);
        $this->addOrocrmContactAddressForeignKeys($schema);
        $this->addOrocrmContactAdrToAdrTypeForeignKeys($schema);
        $this->addOrocrmContactEmailForeignKeys($schema);
        $this->addOrocrmContactGroupForeignKeys($schema);
        $this->addOrocrmContactPhoneForeignKeys($schema);
        $this->addOrocrmContactToContactGrpForeignKeys($schema);
        $this->oroEmailAddressForeignKeys($schema);

        $this->attachmentExtension->addImageRelation($schema, 'orocrm_contact', 'picture', [], 2, 58, 58);
        $this->activityExtension->addActivityAssociation($schema, 'oro_email', 'orocrm_contact');
        $this->activityExtension->addActivityAssociation($schema, 'oro_note', 'orocrm_contact');
    }

    /**
     * Create oro_contact table
     */
    private function createOrocrmContactTable(Schema $schema): void
    {
        $table = $schema->createTable('orocrm_contact');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('assigned_to_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('updated_by_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_by_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('reports_to_contact_id', 'integer', ['notnull' => false]);
        $table->addColumn('method_name', 'string', ['notnull' => false, 'length' => 32]);
        $table->addColumn('source_name', 'string', ['notnull' => false, 'length' => 32]);
        $table->addColumn('name_prefix', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('first_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('middle_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('last_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('name_suffix', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('gender', 'string', ['notnull' => false, 'length' => 8]);
        $table->addColumn('birthday', 'date', ['notnull' => false]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('job_title', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('fax', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('skype', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('twitter', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('facebook', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('google_plus', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('linkedin', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('createdAt', 'datetime');
        $table->addColumn('updatedAt', 'datetime');
        $table->addColumn('email', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['source_name'], 'IDX_403263ED5FA9FB05');
        $table->addIndex(['method_name'], 'IDX_403263ED42F70470');
        $table->addIndex(['user_owner_id'], 'IDX_403263ED9EB185F9');
        $table->addIndex(['organization_id'], 'IDX_403263ED32C8A3DE');
        $table->addIndex(['assigned_to_user_id'], 'IDX_403263ED11578D11');
        $table->addIndex(['reports_to_contact_id'], 'IDX_403263EDF27EBC1E');
        $table->addIndex(['created_by_user_id'], 'IDX_403263ED7D182D95');
        $table->addIndex(['updated_by_user_id'], 'IDX_403263ED2793CC5E');
        $table->addIndex(['last_name', 'first_name', 'id'], 'contact_name_idx');
        $table->addIndex(['first_name'], 'contact_first_name_idx');
        $table->addIndex(['updatedAt'], 'contact_updated_at_idx');
    }

    /**
     * Create oro_contact_address table
     */
    private function createOrocrmContactAddressTable(Schema $schema): void
    {
        $table = $schema->createTable('orocrm_contact_address');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('country_code', 'string', ['notnull' => false, 'length' => 2]);
        $table->addColumn('is_primary', 'boolean', ['notnull' => false]);
        $table->addColumn('label', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('street', 'string', ['notnull' => false, 'length' => 500]);
        $table->addColumn('street2', 'string', ['notnull' => false, 'length' => 500]);
        $table->addColumn('city', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('postal_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('organization', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('region_text', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('name_prefix', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('first_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('middle_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('last_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('name_suffix', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created', 'datetime');
        $table->addColumn('updated', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['owner_id'], 'IDX_CACC16DB7E3C61F9');
        $table->addIndex(['country_code'], 'IDX_CACC16DBF026BB7C');
        $table->addIndex(['region_code'], 'IDX_CACC16DBAEB327AF');
    }

    /**
     * Create oro_contact_adr_to_adr_type table
     */
    private function createOrocrmContactAdrToAdrTypeTable(Schema $schema): void
    {
        $table = $schema->createTable('orocrm_contact_adr_to_adr_type');
        $table->addColumn('contact_address_id', 'integer');
        $table->addColumn('type_name', 'string', ['length' => 16]);
        $table->setPrimaryKey(['contact_address_id', 'type_name']);
        $table->addIndex(['contact_address_id'], 'IDX_3FBCDDC6320EF6E2');
        $table->addIndex(['type_name'], 'IDX_3FBCDDC6892CBB0E');
    }

    /**
     * Create oro_contact_email table
     */
    private function createOrocrmContactEmailTable(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->createTable('orocrm_contact_email');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('is_primary', 'boolean', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['owner_id'], 'IDX_335A28C37E3C61F9');
        $table->addIndex(['email', 'is_primary'], 'primary_email_idx');

        if ($this->platform instanceof PostgreSqlPlatform) {
            $queries->addPostQuery(new SqlMigrationQuery(
                'CREATE INDEX idx_contact_email_ci ON orocrm_contact_email (LOWER(email))'
            ));
        }
    }

    /**
     * Create oro_contact_group table
     */
    private function createOrocrmContactGroupTable(Schema $schema): void
    {
        $table = $schema->createTable('orocrm_contact_group');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('label', 'string', ['length' => 30]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['label'], 'UNIQ_B9081072EA750E8');
        $table->addIndex(['user_owner_id'], 'IDX_B90810729EB185F9');
        $table->addIndex(['organization_id'], 'IDX_B908107232C8A3DE');
    }

    /**
     * Create oro_contact_method table
     */
    private function createOrocrmContactMethodTable(Schema $schema): void
    {
        $table = $schema->createTable('orocrm_contact_method');
        $table->addColumn('name', 'string', ['length' => 32]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->setPrimaryKey(['name']);
        $table->addUniqueIndex(['label'], 'UNIQ_B88D41BEA750E8');
    }

    /**
     * Create oro_contact_phone table
     */
    private function createOrocrmContactPhoneTable(Schema $schema): void
    {
        $table = $schema->createTable('orocrm_contact_phone');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('phone', 'string', ['length' => 255]);
        $table->addColumn('is_primary', 'boolean', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['owner_id'], 'IDX_9087C36A7E3C61F9');
        $table->addIndex(['phone', 'is_primary'], 'primary_phone_idx');
        $table->addIndex(['phone'], 'phone_idx');
    }

    /**
     * Create oro_contact_source table
     */
    private function createOrocrmContactSourceTable(Schema $schema): void
    {
        $table = $schema->createTable('orocrm_contact_source');
        $table->addColumn('name', 'string', ['length' => 32]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->setPrimaryKey(['name']);
        $table->addUniqueIndex(['label'], 'UNIQ_A5B9108EA750E8');
    }

    /**
     * Create oro_contact_to_contact_grp table
     */
    private function createOrocrmContactToContactGrpTable(Schema $schema): void
    {
        $table = $schema->createTable('orocrm_contact_to_contact_grp');
        $table->addColumn('contact_id', 'integer');
        $table->addColumn('contact_group_id', 'integer');
        $table->setPrimaryKey(['contact_id', 'contact_group_id']);
        $table->addIndex(['contact_id'], 'IDX_885CCB12E7A1254A');
        $table->addIndex(['contact_group_id'], 'IDX_885CCB12647145D0');
    }

    /**
     * Add oro_contact foreign keys.
     */
    private function addOrocrmContactForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orocrm_contact');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['assigned_to_user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['updated_by_user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['created_by_user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_contact'),
            ['reports_to_contact_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_contact_method'),
            ['method_name'],
            ['name'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_contact_source'),
            ['source_name'],
            ['name'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_contact_address foreign keys.
     */
    private function addOrocrmContactAddressForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orocrm_contact_address');
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_contact'),
            ['owner_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_region'),
            ['region_code'],
            ['combined_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_country'),
            ['country_code'],
            ['iso2_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Add oro_contact_adr_to_adr_type foreign keys.
     */
    private function addOrocrmContactAdrToAdrTypeForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orocrm_contact_adr_to_adr_type');
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_contact_address'),
            ['contact_address_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_address_type'),
            ['type_name'],
            ['name'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Add oro_contact_email foreign keys.
     */
    private function addOrocrmContactEmailForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orocrm_contact_email');
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_contact'),
            ['owner_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_contact_group foreign keys.
     */
    private function addOrocrmContactGroupForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orocrm_contact_group');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_contact_phone foreign keys.
     */
    private function addOrocrmContactPhoneForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orocrm_contact_phone');
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_contact'),
            ['owner_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_contact_to_contact_grp foreign keys.
     */
    private function addOrocrmContactToContactGrpForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orocrm_contact_to_contact_grp');
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_contact'),
            ['contact_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_contact_group'),
            ['contact_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_email_address foreign keys.
     */
    private function oroEmailAddressForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_address');
        $table->addColumn('owner_contact_id', 'integer', ['notnull' => false]);
        $table->addIndex(['owner_contact_id'], 'IDX_FC9DBBC5B5CBBC0F');
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_contact'),
            ['owner_contact_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }
}
