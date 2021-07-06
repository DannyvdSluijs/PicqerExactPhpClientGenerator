namespace Picqer\Financials\Exact;

/**
 * Class <?php echo $endpoint->getClassName(); ?>.
 *
 * @see <?php echo $endpoint->documentation . PHP_EOL; ?>
 *
<?php foreach ($endpoint->getNonObsoleteProperties() as $property): ?>
 * <?php echo $property->toPhpDoc(); ?>
<?php endforeach; ?>
 */
class <?php echo $endpoint->getClassName(); ?> extends Model
{
    use Query\Findable;
<?php if ($endpoint->supportsPostMethod() || $endpoint->supportsPutMethod()): ?>
    use Persistance\Storable;
<?php endif; ?>
<?php if(in_array('Persistance\Downloadable', $traits)): ?>
    use Persistance\Downloadable;
<?php endif; ?>

<?php if ($endpoint->hasNonDefaultPrimaryKeyProperty()): ?>
    protected $primaryKey = '<?php echo $endpoint->primaryKeyProperty()->name; ?>';

<?php endif; ?>
<?php foreach ($properties as $property): ?>
    <?php echo $property . PHP_EOL; ?>

<?php endforeach; ?>
    protected $fillable = [
<?php foreach ($endpoint->getNonObsoleteProperties() as $property): ?>
        '<?php echo $property->name; ?>',
<?php endforeach; ?>
    ];

    protected $url = '<?php echo $endpoint->getClassUri(); ?>';
<?php foreach ($methods as $method): ?>

    <?php echo $method . PHP_EOL; ?>
<?php endforeach; ?>
}
