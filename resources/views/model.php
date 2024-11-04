<?php
/**
 * @var $endpoint \PicqerExactPhpClientGenerator\EndpointClassFacade
 **/

if (!\is_null($endpoint->strictTypes)): ?>
declare(strict_types=<?php echo $endpoint->strictTypes; ?>);

<?php endif; ?>
namespace Picqer\Financials\Exact;

/**
 * Class <?php echo $endpoint->className; ?>.
 *
 * @see <?php echo $endpoint->documentation . PHP_EOL; ?>
<?php if (!is_null($endpoint->deprecationDocComment) && !$endpoint->deprecated):
    echo $endpoint->deprecationDocComment . PHP_EOL;
endif; ?>
<?php if ($endpoint->deprecated): ?>
 * @deprecated ExactOnline has indicated this endpoint is deprecated, for more details see the documentation page
<?php endif; ?>
 *
<?php foreach ($endpoint->nonObsoleteProperties as $property): ?>
 * <?php echo $property->toPhpDoc(); ?>
<?php endforeach; ?>
<?php if (!is_null($endpoint->additionalClassDocComment)):
    echo $endpoint->additionalClassDocComment . PHP_EOL;
endif; ?>
 */
class <?php echo $endpoint->className; ?> extends Model
{
    use Query\Findable;
<?php if ($endpoint->supportsPostMethod() || $endpoint->supportsPutMethod()): ?>
    use Persistance\Storable;
<?php endif; ?>
<?php if(in_array('Persistance\Downloadable', $endpoint->traits, true)): ?>
    use Persistance\Downloadable;
<?php endif; ?>

<?php if ($endpoint->hasNonDefaultPrimaryKeyProperty()): ?>
    protected $primaryKey = '<?php echo $endpoint->primaryKeyProperty->name; ?>';

<?php endif; ?>
<?php foreach ($endpoint->properties as $property): ?>
    <?php echo $property . PHP_EOL; ?>

<?php endforeach; ?>
    protected $fillable = [
<?php foreach ($endpoint->nonObsoleteProperties as $property): ?>
        '<?php echo $property->name; ?>',
<?php endforeach; ?>
    ];

    protected $url = '<?php echo $endpoint->getClassUri(); ?>';
<?php foreach ($endpoint->functions as $function): ?>

<?php echo $function . PHP_EOL; ?>
<?php endforeach; ?>
}
