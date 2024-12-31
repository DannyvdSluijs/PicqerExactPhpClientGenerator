<?php
/**
 * @var  EndpointClassFacade $endpoint
 **/

use PicqerExactPhpClientGenerator\EndpointClassFacade;
use PicqerExactPhpClientGenerator\NamingHelper;

if (!\is_null($endpoint->strictTypes)): ?>
declare(strict_types=<?php echo $endpoint->strictTypes; ?>);

<?php endif; ?>
namespace Picqer\Financials\Exact;

/**
 * Class <?php echo NamingHelper::getClassName($endpoint); ?>.
 *
 * @see <?php echo $endpoint->documentation . PHP_EOL; ?>
<?php if (!is_null($endpoint->deprecationDocComment) && !$endpoint->isDeprecated):
    echo $endpoint->deprecationDocComment . PHP_EOL;
endif; ?>
<?php if ($endpoint->isDeprecated): ?>
 * @deprecated ExactOnline has indicated this endpoint is deprecated, for more details see the documentation page
<?php endif; ?>
 *
<?php foreach ($endpoint->nonObsoleteProperties as $property): ?>
 <?php
    $description = $property->description;
    if (str_starts_with($description, "http")) {
        $description = sprintf('See %s for more explanation', $description);
    }
    echo trim(sprintf('* @property %s $%s %s', NamingHelper::toPhpPropertyType($endpoint, $property), $property->name, $description)) . PHP_EOL;
?>
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
<?php
    foreach ($endpoint->nonEdmProperties as $property):
        break;
        $functionName = sprintf('get%s', $property->name);
        if (array_key_exists($functionName, $endpoint->functions)) { continue; }
?>
    public function <?php echo $functionName; ?>()
    {
        if (array_key_exists('__deferred', $this->attributes['<?php echo $property->name; ?>'])) {
            // @todo correct this primary key for foreign key id
            $this->attributes['<?php echo $property->name; ?>'] = (new <?php echo $property->type; ?>($this->connection()))->filter("<?php echo $endpoint->primaryKeyProperty->name; ?> eq guid'{$this-><?php echo $endpoint->primaryKeyProperty->name; ?>}'");
        }
        return $this->attributes['<?php echo $property->name; ?>'];
    }
<?php endforeach; ?>
}
