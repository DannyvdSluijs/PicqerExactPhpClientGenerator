<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator\Decorator;

use Symfony\Component\String\Inflector\EnglishInflector;

/**
 * @property string documentation
 */
class EndpointDecorator
{
    private ?EnglishInflector $inflector;

    public function __construct(
        private \stdClass $endpoint,
        private string $path
    )
    {
    }

    public function __get(string $param)
    {
        return match ($param) {
            'properties' => $this->decorateProperties($this->endpoint->properties),
            'documentation' => $this->decorateDocumentation(),
            default => $this->endpoint->{$param}
        };
    }

    public function getFilename(): string
    {
        return sprintf('%s/src/Picqer/Financials/Exact/%s.php', $this->path, $this->getClassName());
    }

    public function getNonObsoleteProperties(): array
    {
        return $this->decorateProperties(array_filter(
            $this->endpoint->properties,
            static function ($p): bool {
                return !$p->hidden && !str_contains(strtolower($p->description), 'obsolete');
            }
        ));
    }

    public function getClassName(): string
    {
        $isSyncEndpoint = str_contains($this->endpoint->uri, '/api/v1/{division}/sync');

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

        if (array_key_exists($this->endpoint->uri, $namingConventionExceptions)) {
            return $namingConventionExceptions[$this->endpoint->uri];
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

        if (array_key_exists($this->endpoint->endpoint, $exceptions)) {
            if ($isSyncEndpoint) {
                return 'Sync' . $exceptions[$this->endpoint->endpoint];
            }
            return $exceptions[$this->endpoint->endpoint];
        }

        $inflector = $this->getInflector();
        $parts = explode('/', $this->endpoint->endpoint);
        $className = array_pop($parts);
        $singleOptions = $inflector->singularize($className);
        $name = array_pop($singleOptions);

        if ($isSyncEndpoint) {
            return 'Sync' . $name;
        }

        return $name;
    }

    public function getClassUri(): string
    {
        if (str_starts_with($this->endpoint->uri, '/api/v1/{division}/')) {
            return substr($this->endpoint->uri, 19);
        }

        if (str_starts_with($this->endpoint->uri, '/api/v1/')) {
            return substr($this->endpoint->uri, 8);
        }

        return  $this->endpoint->uri;
    }

    public function hasNonDefaultPrimaryKeyProperty(): bool
    {
        $primaryKeyProperty = $this->primaryKeyProperty();
        return $primaryKeyProperty && $primaryKeyProperty->name !== 'ID';
    }

    public function primaryKeyProperty(): ?PropertyDecorator
    {
        $primaryKeyProperties = array_filter(
            $this->endpoint->properties,
            static fn ($prop) => $prop->primaryKey
        );

        if (count($primaryKeyProperties) === 0) {
            return null;
        }

        return new PropertyDecorator(array_pop($primaryKeyProperties), $this);
    }

    public function supportsPostMethod(): bool
    {
        return $this->endpoint->supportedMethods->post;
    }

    public function supportsPutMethod(): bool
    {
        return $this->endpoint->supportedMethods->post;
    }

    private function getInflector(): EnglishInflector
    {
        if (!isset($this->inflector)) {
            $this->inflector = new EnglishInflector();
        }

        return $this->inflector;
    }

    private function decorateProperties(array $properties): array
    {
        return array_map(fn($p): \PicqerExactPhpClientGenerator\Decorator\PropertyDecorator => new PropertyDecorator($p, $this), $properties);
    }

    private function decorateDocumentation(): string
    {
        return str_replace(
            ['Crm', 'Hrm'],
            ['CRM', 'HRM'],
            $this->endpoint->documentation
        );
    }
}