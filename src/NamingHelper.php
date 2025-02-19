<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator;

use PicqerExactPhpClientGenerator\ValueObject\Endpoint;
use PicqerExactPhpClientGenerator\ValueObject\Property;
use Symfony\Component\String\Inflector\EnglishInflector;

class NamingHelper
{
    public static function getClassName(Endpoint|EndpointClassFacade $endpoint): string
    {
        $isSyncEndpoint = str_contains($endpoint->uri, '/api/v1/{division}/sync');
        $isBulkEndpoint = str_contains($endpoint->uri, '/api/v1/{division}/bulk');

        // Some cases don't follow the naming conventions, have naming clashes within Exact or are reserved keywords in PHP
        $namingConventionExceptions = [
            '/api/v1/system/Users' => 'SystemUser',
            '/api/v1/{division}/system/Divisions' => 'SystemDivision',
            '/api/v1/{division}/hrm/Divisions' => 'HrmDivision',
            '/api/v1/{division}/vat/VATCodes' => 'VatCode',
            '/api/v1/{division}/read/financial/Returns' => 'Returns', // Return is a reserved keyword in PHP
            '/api/v1/{division}/openingbalance/PreviousYear/AfterEntry' =>'PreviousYearAfterEntry',
            '/api/v1/{division}/sync/Cashflow/PaymentTerms' => 'SyncPaymentTerm',
            '/api/v1/{division}/openingbalance/PreviousYear/Processed' => 'PreviousYearProcessed',
            '/api/v1/{division}/read/project/RecentCostsByNumberOfWeeks' => 'RecentCostsByNumberOfWeeks',
            '/api/v1/{division}/read/project/RecentHoursByNumberOfWeeks' => 'RecentHoursByNumberOfWeeks',
            '/api/v1/{division}/read/crm/Documents' => 'CrmDocument',
            '/api/v1/{division}/read/crm/DocumentsAttachments' => 'CrmDocumentAttachment',
            '/api/v1/{division}/read/financial/RevenueListByYearAndStatus?year={Edm.Int32}&afterEntry={Edm.Boolean}' => 'RevenueListByYearAndStatus',
            '/api/v1/{division}/logistics/ReasonCodes' => 'LogisticsReasonsCodes', // As it collides with '/api/v1/{division}/crm/ReasonCodes'
            '/api/v1/{division}/openingbalance/CurrentYear/AfterEntry' => 'CurrentYearAfterEntry',
            '/api/v1/{division}/openingbalance/CurrentYear/Processed' => 'CurrentYearProcessed',
            '/api/v1/{division}/sync/Inventory/ItemWarehouses' => 'SyncInventoryItemWarehouse',
            '/api/v1/{division}/manufacturing/TimeTransactions' => 'ManufacturingTimeTransaction',
            '/api/v1/{division}/project/TimeTransactions' => 'ProjectTimeTransaction',

        ];

        if (array_key_exists($endpoint->uri, $namingConventionExceptions)) {
            return $namingConventionExceptions[$endpoint->uri];
        }

        // Some cases aren't properly handled by inflector
        $exceptions = [
            'EmploymentContractFlexPhases' => 'EmploymentContractFlexPhase',
            'ItemWarehouses' => 'ItemWarehouse',
            'Inventory/ItemWarehouses' => 'InventoryItemWarehouse',
            'Warehouses' => 'Warehouse',
            'BulkProjectProjectWBS' => 'BulkProjectProjectWBS',
            'ReadProjectProjectWBSByProjectAndWBS' => 'ReadProjectProjectWBSByProjectAndWBS',
            'SalesItemPrice' => 'SalesItemPrice',
            'TimeAndBillingActivitiesAndExpenses' => 'TimeAndBillingActivitiesAndExpense',
            'TimeAndBillingEntryRecentActivitiesAndExpenses' => 'TimeAndBillingEntryRecentActivitiesAndExpense',
            'WBSExpenses' => 'WBSExpense',
            'Project/ProjectWBS' => 'ProjectWBS',
            'ProjectWBSByProjectAndWBS' => 'ProjectWBSByProjectAndWBS',
            'RevenueListByYearAndStatus' => 'RevenueListByYearAndStatus',
            'LeadPurposes' => 'LeadPurpose',
        ];

        if (array_key_exists($endpoint->name, $exceptions)) {
            if ($isSyncEndpoint) {
                return 'Sync' . $exceptions[$endpoint->name];
            }
            return $exceptions[$endpoint->name];
        }

        $inflector = new EnglishInflector();
        $parts = explode('/', $endpoint->name);
        $className = array_pop($parts);
        $singleOptions = $inflector->singularize($className);
        $name = array_pop($singleOptions);

        return match (true) {
            $isSyncEndpoint => 'Sync' . $name,
            $isBulkEndpoint => 'Bulk' . $name,
            default => $name,
        };
    }

    public static function toPhpPropertyType(Endpoint|EndpointClassFacade $endpoint, Property $property): string
    {
        $type = self::fetchTypeForPropertyOfEndpoint($endpoint, $property);
        if (!\is_null($type)) {
            return $type;
        }

        // Some types are mis-documented
        if ($property->type === 'Exact.Web.Api.Models.HRM.DivisionClass' || str_starts_with($property->type, 'Class_')) {
            return 'DivisionClass';
        }
        if ($property->type === 'Attachments' && self::getClassName($endpoint) === 'CrmDocument') {
            return 'CrmDocumentAttachment[]';
        }
        if ($property->type === 'Attachments') {
            return 'DocumentAttachment[]';
        }

        if ($property->type === 'Exact.Web.Api.Models.Manufacturing.MaterialPlanCalculator') {
            return 'string';
        }

        if (str_starts_with($property->description, 'Collection') || ! str_starts_with($property->type, 'Edm.')) {
            // Some cases aren't properly handled by inflector
            $exceptions = [
                'EmploymentContractFlexPhases' => 'EmploymentContractFlexPhase',
                'ItemWarehouses' => 'ItemWarehouse',
                'Warehouses' => 'Warehouse',
            ];

            if (array_key_exists($property->type, $exceptions)) {
                return $exceptions[$property->type] . '[]';
            }

            $inflector = new EnglishInflector();
            $singleOptions = $inflector->singularize($property->type);

            return array_pop($singleOptions) . '[]';
        }

        return match ($property->type) {
            'Edm.Int64', 'Edm.Int32', 'Edm.Int16', 'Edm.Byte' => 'int',
            'Edm.Double' => 'float',
            'Edm.Boolean' => 'bool',
            default => 'string'
        };
    }

    private static function fetchTypeForPropertyOfEndpoint(Endpoint|EndpointClassFacade $endpoint, Property $property): ?string
    {
        try {
            return match ($endpoint->uri) {
                '/api/v1/{division}/inventory/AssemblyOrders' => match ($property->type) {
                    'PartItems' => 'mixed',
                },
                '/api/v1/{division}/bulk/SalesOrder/GoodsDeliveries' => match ($property->type) {
                    'GoodsDeliveryLines' => 'GoodsDeliveryLine',
                },
                '/api/v1/{division}/bulk/SalesOrder/GoodsDeliveryLines' => match ($property->type) {
                    'StockBatchNumbers' => 'StockBatchNumber[]',
                    'StockSerialNumbers' => 'StockSerialNumber[]',
                },
                '/api/v1/{division}/salesorder/DigitalOrderPickingLines' => match ($property->type) {
                    'PickingLocations' => 'DigitalOrderPickingLine[]',
                },
                '/api/v1/{division}/hrm/Divisions' => match ($property->type) {
                    'DivisionClasses' => 'DivisionClass',
                },
                '/api/v1/{division}/logistics/ItemAssortment' => match ($property->type) {
                    'Properties' => 'ItemAssortmentProperty[]',
                },
                '/api/v1/{division}/logistics/ReasonCodes' => match ($property->type) {
                    'Types' => 'ReasonCodesLinkType[]'
                },
                '/api/v1/{division}/read/financial/PayablesList',
                '/api/v1/{division}/read/financial/PayablesListByAccount',
                '/api/v1/{division}/read/financial/PayablesListByAccountAndAgeGroup',
                '/api/v1/{division}/read/financial/PayablesListByAgeGroup',
                '/api/v1/{division}/read/financial/ReceivablesList',
                '/api/v1/{division}/read/financial/ReceivablesListByAccount',
                '/api/v1/{division}/read/financial/ReceivablesListByAccountAndAgeGroup',
                '/api/v1/{division}/read/financial/ReceivablesListByAgeGroup' => match ($property->type) {
                    'Notes' => 'string[]',
                },
                '/api/v1/{division}/cashflow/ProcessPayments' => match ($property->type) {
                    'PaymentIDs' => '\stdClass[]',
                },
                '/api/v1/{division}/project/Projects' => match ($property->type) {
                    'BudgetedHoursPerHourType' => 'ProjectHourBudget',
                },
                '/api/v1/{division}/logistics/ReasonForLogistics' => match ($property->type) {
                    'Types' => 'ReasonForLogisticsLinkType[]',
                },
                '/api/v1/{division}/hrm/Schedules' => match ($property->type) {
                    'ScheduleEntries' => 'mixed[]'
                },
                '/api/v1/{division}/manufacturing/ShopOrders' => match ($property->type) {
                    'SalesOrderlines' => 'SalesOrderLine[]'
                },
                '/api/v1/{division}/manufacturing/ShopOrderRoutingStepPlans' => match ($property->type) {
                    'TimeTransactions' => 'ManufacturingTimeTransaction[]',
                },
                'users/Users' => match ($property->type) {
                    'UserRoles' => 'UserRole[]',
                },
                '/api/v1/{division}/vat/VATCodes' => match ($property->type) {
                    'VATPercentages' => 'VatPercentage[]'
                }
            };
        } catch (\UnhandledMatchError) {
            return null;
        }
    }
}
