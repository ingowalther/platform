<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Order\Aggregate\OrderLineItem;

use Shopware\Core\Checkout\Order\Aggregate\OrderDeliveryPosition\OrderDeliveryPositionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\Collection\OrderLineItemBasicCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\Struct\OrderLineItemBasicStruct;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\ORM\EntityDefinition;
use Shopware\Core\Framework\ORM\EntityExtensionInterface;
use Shopware\Core\Framework\ORM\Field\DateField;
use Shopware\Core\Framework\ORM\Field\FkField;
use Shopware\Core\Framework\ORM\Field\FloatField;
use Shopware\Core\Framework\ORM\Field\IdField;
use Shopware\Core\Framework\ORM\Field\IntField;
use Shopware\Core\Framework\ORM\Field\LongTextField;
use Shopware\Core\Framework\ORM\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\ORM\Field\OneToManyAssociationField;
use Shopware\Core\Framework\ORM\Field\ReferenceVersionField;
use Shopware\Core\Framework\ORM\Field\StringField;
use Shopware\Core\Framework\ORM\Field\TenantIdField;
use Shopware\Core\Framework\ORM\Field\VersionField;
use Shopware\Core\Framework\ORM\FieldCollection;
use Shopware\Core\Framework\ORM\Write\Flag\CascadeDelete;
use Shopware\Core\Framework\ORM\Write\Flag\PrimaryKey;
use Shopware\Core\Framework\ORM\Write\Flag\ReadOnly;
use Shopware\Core\Framework\ORM\Write\Flag\Required;
use Shopware\Core\Framework\ORM\Write\Flag\SearchRanking;

class OrderLineItemDefinition extends EntityDefinition
{
    /**
     * @var FieldCollection
     */
    protected static $primaryKeys;

    /**
     * @var FieldCollection
     */
    protected static $fields;

    /**
     * @var EntityExtensionInterface[]
     */
    protected static $extensions = [];

    public static function getEntityName(): string
    {
        return 'order_line_item';
    }

    public static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            new TenantIdField(),
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            new VersionField(),

            (new FkField('order_id', 'orderId', OrderDefinition::class))->setFlags(new Required()),
            (new ReferenceVersionField(OrderDefinition::class))->setFlags(new Required()),

            (new StringField('identifier', 'identifier'))->setFlags(new Required()),
            (new IntField('quantity', 'quantity'))->setFlags(new Required()),
            (new FloatField('unit_price', 'unitPrice'))->setFlags(new Required()),
            (new FloatField('total_price', 'totalPrice'))->setFlags(new Required()),
            (new LongTextField('payload', 'payload'))->setFlags(new Required(), new SearchRanking(self::HIGH_SEARCH_RANKING)),
            new StringField('parent_id', 'parentId'),
            new StringField('type', 'type'),
            new DateField('created_at', 'createdAt'),
            new DateField('updated_at', 'updatedAt'),
            new ManyToOneAssociationField('order', 'order_id', OrderDefinition::class, false),
            (new OneToManyAssociationField('orderDeliveryPositions', OrderDeliveryPositionDefinition::class, 'order_line_item_id', false, 'id'))->setFlags(new CascadeDelete(), new ReadOnly()),
        ]);
    }


    public static function getBasicCollectionClass(): string
    {
        return OrderLineItemBasicCollection::class;
    }

    public static function getBasicStructClass(): string
    {
        return OrderLineItemBasicStruct::class;
    }
}
