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
<?php if ($endpoint->supportsPostMethod()): ?>
    use Persistance\Storable;
<?php endif; ?>

<?php if ($endpoint->hasNonDefaultPrimaryKeyProperty()): ?>
    protected $primaryKey = '<?php echo $endpoint->primaryKeyProperty()->name; ?>';

<?php endif; ?>
    protected $fillable = [
<?php foreach ($endpoint->getNonObsoleteProperties() as $property): ?>
        '<?php echo $property->name; ?>',
<?php endforeach; ?>
    ];

    protected $url = '<?php echo $endpoint->getClassUri(); ?>';
}
