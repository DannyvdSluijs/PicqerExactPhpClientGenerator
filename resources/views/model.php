namespace Picqer\Financials\Exact;

/**
 * Class <?php echo substr($endpoint->endpoint, 0, -1); ?>.
 *
 * @package Picqer\Financials\Exact
 * @see <?php echo $endpoint->documentation . PHP_EOL; ?>
 *
<?php foreach ($endpoint->properties as $property): ?>
 * @property <?php echo sprintf("%s \$%s %s\n", $property->type, $property->name, $property->description); ?>
<?php endforeach; ?>
 */
class <?php echo substr($endpoint->endpoint, 0, -1); ?> extends Model
{
    use Query\Findable;
    use Persistance\Storable;

    protected $fillable = [
<?php foreach ($endpoint->properties as $property): ?>
        '<?php echo $property->name; ?>',
<?php endforeach; ?>
    ];

    protected $url = '<?php echo substr($endpoint->uri, 19); ?>';
}
