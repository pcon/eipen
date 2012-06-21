<?php

require_once 'Image/Graph.php';

$graph =& Image_Graph::factory('graph', array(500, 300));

$font =& $graph->addNew('font', 'Verdana');
$font->setSize(8);
$graph->setFont($Font);

$plotarea =& $graph->addNew('plotarea');

$data =& Image_Graph::factory('dataset');
$data->addPoint('2008-12-16',0);
$data->addPoint('2008-12-17',4);
$data->addPoint('2008-12-18',5);
$data->addPoint('2008-12-19',10);
$data->addPoint('2008-12-20',0);
$data->addPoint('2008-12-21',3);
$data->addPoint('2008-12-22',0);

$plot =& $plotarea->addNew('bar', &$data); 
$plot->setLineColor('black'); 
$plot->setFillColor('red');

$graph->done();

?>
