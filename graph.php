<?
if(IsSet($_REQUEST["XDEBUG_SESSION_START"]))
{
	$_REQUEST['tbl']='elc';
	$_REQUEST['column']='pm';
}

require_once ("config.php");
require_once ('jpgraph/jpgraph.php');
require_once ('jpgraph/jpgraph_line.php');
require_once ('jpgraph/jpgraph_bar.php');

$column = $_REQUEST['column'];
$table = $_REQUEST['tbl'];

$db = new PDO('sqlite:'. DB_SLRC_NAME);
$values = $db->query("SELECT fe, $column FROM $table ORDER BY fe");

$ydata = array();
if (count($values)>0) {
	foreach ($values as $rij) {
		$xdata[] = $rij['fe'];
		$ydata[] = $rij[$column];
	}
}
$db = null;

// Create a graph instance
$graph = new Graph(1200,600);
$graph->SetScale('intint');
$graph->xaxis->title->Set("Graph $column of table $table");
$graph->yaxis->title->Set($column);

// Create the linear plot
$lineplot=new LinePlot($ydata);
$graph->Add($lineplot);
$graph->Stroke();

?>
