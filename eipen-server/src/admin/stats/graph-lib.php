<?php
	require_once 'Image/Graph.php';

	function drawGraph ($input, $type) {
		$graph =& Image_Graph::factory('graph', array(500, 300));

		$font =& $graph->addNew('font', 'Verdana');
		$font->setSize(8);
		$graph->setFont($Font);

		$plotarea =& $graph->addNew('plotarea');

		$data =& Image_Graph::factory('dataset');
		foreach ($input as $point) {
			$data->addPoint($point['label'],$point['data']);
		}

		$grid =& $plotarea->addNew('line_grid', IMAGE_GRAPH_AXIS_Y);
		$grid->setLineColor('black');

		$plot =& $plotarea->addNew($type, &$data);
		$plot->setLineColor('black');
		$plot->setFillColor('red');

		$graph->done();
	}

	function getPastSevenDays () {
		$days = array();

		for($i=7;$i>=0; $i--) {
			list($year,$month,$day) = split('-',date('Y-m-d', time()));

			$day -= $i;
			while ($day < 1) {
				$month -= 1;

				if ($month == 0) {
					$year -= 1;
					$month = 12;
				}

				$offset = $day;

				$day = date('t', mktime(0,0,0,$month,1,$year));

				$day += $offset;
			}

			$time = mktime(0, 0, 0, $month, $day, $year);
			
			$label = date('Y-m-d', $time);

			$day = array('label'=>$label, 'data'=>0);
			$days[]=$day;
		}
		
		return $days;
	}
?>
