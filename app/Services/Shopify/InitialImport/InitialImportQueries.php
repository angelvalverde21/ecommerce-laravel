<?php

namespace App\Services\Shopify\InitialImport;

class InitialImportQueries
{
    public const ENTITIES = ['orders', 'order_items', 'shipping_lines', 'discounts', 'transactions', 'fulfillments', 'fulfillment_items', 'fulfillment_events', 'refunds', 'refund_items', 'refund_shipping_lines', 'refund_adjustments', 'products', 'variants', 'images', 'customers', 'addresses', 'metafields', 'events', 'event_attachments', 'records'];
    public const ROOTS = ['orders' => 'orders', 'products' => 'products', 'variants' => 'productVariants', 'customers' => 'customers'];
    public const SNAPSHOTS = ['customer_snapshot' => 'customers', 'product_snapshot' => 'products', 'variant_snapshot' => 'variants'];
    public const CONNECTIONS = [
        'order_items' => ['Order', 'lineItems', 'order_items'],
        'shipping_lines' => ['Order', 'shippingLines', 'shipping_lines'],
        'discounts' => ['Order', 'discountApplications', 'discounts'],
        'order_metafields' => ['Order', 'metafields', 'metafields'],
        'events' => ['Order', 'events', 'events'],
        'fulfillment_items' => ['Fulfillment', 'fulfillmentLineItems', 'fulfillment_items'],
        'fulfillment_events' => ['Fulfillment', 'events', 'fulfillment_events'],
        'refund_items' => ['Refund', 'refundLineItems', 'refund_items'],
        'refund_shipping_lines' => ['Refund', 'refundShippingLines', 'refund_shipping_lines'],
        'refund_adjustments' => ['Refund', 'orderAdjustments', 'refund_adjustments'],
        'images' => ['Product', 'images', 'images'],
        'product_metafields' => ['Product', 'metafields', 'metafields'],
        'variant_metafields' => ['ProductVariant', 'metafields', 'metafields'],
        'addresses' => ['Customer', 'addressesV2', 'addresses'],
        'customer_metafields' => ['Customer', 'metafields', 'metafields'],
    ];

    public static function fields(string $entity, bool $includeStaff = false): string
    {
        $money = '{ shopMoney { amount currencyCode } presentmentMoney { amount currencyCode } }';
        $address = 'id name firstName lastName company phone address1 address2 city province provinceCode country countryCodeV2 zip';
        $tax = 'taxLines { title rate priceSet '.$money.' }';
        $value = 'value { __typename ... on MoneyV2 { amount currencyCode } ... on PricingPercentageValue { percentage } }';
        return match ($entity) {
            'orders' => '__typename id legacyResourceId name createdAt updatedAt processedAt cancelledAt cancelReason closedAt displayFinancialStatus displayFulfillmentStatus currencyCode presentmentCurrencyCode sourceName email phone note tags paymentGatewayNames customAttributes { key value } discountCodes taxesIncluded taxExempt confirmed test customer { id displayName email phone } shippingAddress { '.$address.' } billingAddress { '.$address.' } '.$tax.' totalPriceSet '.$money.' currentTotalPriceSet '.$money.' currentSubtotalPriceSet '.$money.' currentTotalTaxSet '.$money.' totalShippingPriceSet '.$money.' totalDiscountsSet '.$money.' totalRefundedSet '.$money.' totalOutstandingSet '.$money.' transactions { __typename id kind status gateway formattedGateway createdAt processedAt authorizationCode errorCode test amountSet '.$money.' parentTransaction { id } receiptJson } fulfillments { __typename id name status displayStatus createdAt updatedAt deliveredAt estimatedDeliveryAt totalQuantity trackingInfo { company number url } location { id name } } refunds { __typename id createdAt note totalRefundedSet '.$money.' }',
            'products' => '__typename id legacyResourceId title handle descriptionHtml vendor productType status tags createdAt updatedAt onlineStoreUrl options { id name values } featuredImage { id url altText width height }',
            'variants' => '__typename id legacyResourceId title sku barcode price compareAtPrice inventoryQuantity taxable createdAt updatedAt selectedOptions { name value } product { id title } image { id url altText width height }',
            'customers' => '__typename id legacyResourceId displayName firstName lastName email phone note tags state verifiedEmail taxExempt createdAt updatedAt defaultAddress { '.$address.' }',
            'order_items' => '__typename id name title variantTitle sku quantity currentQuantity refundableQuantity requiresShipping taxable customAttributes { key value } '.$tax.' originalUnitPriceSet '.$money.' discountedUnitPriceSet '.$money.' originalTotalSet '.$money.' discountedTotalSet '.$money.' discountAllocations { allocatedAmountSet '.$money.' discountApplication { index '.$value.' } } variant { id sku title selectedOptions { name value } product { id title } }',
            'shipping_lines' => '__typename id title code source carrierIdentifier requestedFulfillmentService { id } originalPriceSet '.$money.' discountedPriceSet '.$money.' '.$tax.' discountAllocations { allocatedAmountSet '.$money.' discountApplication { index } }',
            'discounts' => '__typename index allocationMethod targetSelection targetType '.$value.' ... on DiscountCodeApplication { code } ... on ManualDiscountApplication { title description } ... on ScriptDiscountApplication { title } ... on AutomaticDiscountApplication { title }',
            'fulfillment_items' => '__typename id quantity lineItem { id } originalTotalSet '.$money.' discountedTotalSet '.$money,
            'fulfillment_events' => '__typename id status happenedAt message city province country zip',
            'refund_items' => '__typename id quantity restockType subtotalSet '.$money.' totalTaxSet '.$money.' lineItem { id } location { id name }',
            'refund_shipping_lines' => '__typename id subtotalAmountSet '.$money.' taxAmountSet '.$money.' shippingLine { id }',
            'refund_adjustments' => '__typename id reason amountSet '.$money.' taxAmountSet '.$money,
            'images' => '__typename id url altText width height',
            'addresses' => '__typename '.$address,
            'metafields' => '__typename id namespace key type value createdAt updatedAt',
            'events' => '__typename id createdAt message action appTitle attributeToApp attributeToUser criticalAlert '
                .'... on BasicEvent { author arguments additionalContent additionalData hasAdditionalContent secondaryMessage subjectId subjectType subject { __typename ... on Node { id } } } '
                .'... on CommentEvent { rawMessage edited canEdit canDelete '.($includeStaff ? 'staffAuthor: author { id name } ' : '').'subject { __typename id } '
                .'embed { __typename ... on Customer { id displayName } ... on DraftOrder { id name } ... on InventoryTransfer { id } ... on Order { id name } ... on Product { id title } ... on ProductVariant { id title } } '
                .'attachments { id name fileExtension size url image { id url altText width height } } }',
            default => throw new \InvalidArgumentException('Entidad de importación no admitida.'),
        };
    }

    public static function bulk(string $kind): string
    {
        return '{ '.self::ROOTS[$kind].' { edges { node { '.self::fields($kind).' } } } }';
    }

    public static function newOrders(): string
    {
        return 'query NewImportedOrders($query: String!, $cursor: String) { orders(first: 50, sortKey: CREATED_AT, query: $query, after: $cursor) { nodes { '.self::fields('orders').' } pageInfo { hasNextPage endCursor } } }';
    }

    public static function snapshots(string $kind): string
    {
        $entity = self::SNAPSHOTS[$kind];
        $type = ['customers' => 'Customer', 'products' => 'Product', 'variants' => 'ProductVariant'][$entity];
        return 'query ImportedOrderReferences($ids: [ID!]!) { nodes(ids: $ids) { ... on '.$type.' { '.self::fields($entity).' } } }';
    }

    public static function connection(string $kind, bool $includeStaff = false): string
    {
        [$owner, $field, $entity] = self::CONNECTIONS[$kind];
        $audit = $kind === 'events' ? 'hasTimelineComment ' : '';
        return 'query ImportPage($id: ID!, $cursor: String) { node(id: $id) { ... on '.$owner.' { '.$audit.$field.'(first: 25, after: $cursor) { edges { cursor node { '.self::fields($entity, $includeStaff).' } } pageInfo { hasNextPage endCursor } } } } }';
    }

    public static function batch(string $kind, bool $includeStaff = false): string
    {
        [$owner, $field, $entity] = self::CONNECTIONS[$kind];
        $audit = $kind === 'events' ? 'hasTimelineComment ' : '';
        return 'query ImportBatch($ids: [ID!]!) { nodes(ids: $ids) { id ... on '.$owner.' { '.$audit.$field.'(first: 25) { edges { cursor node { '.self::fields($entity, $includeStaff).' } } pageInfo { hasNextPage endCursor } } } } }';
    }
}
