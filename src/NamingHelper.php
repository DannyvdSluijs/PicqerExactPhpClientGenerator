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

        if ($isSyncEndpoint) {
            return 'Sync' . $name;
        }

        return $name;
    }

    public static function toPhpPropertyType(Endpoint|EndpointClassFacade $endpoint, Property $property): string
    {
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
}
