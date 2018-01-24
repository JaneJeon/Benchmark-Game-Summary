<?php
// note: this operation is fairly long (1min+). So either rework this to update via AJAX or
// allow nginx to take longer to display a page, because otherwise it'd break

// configs
$langs = ['JavaScript' => 'node',
          'Java' => 'java',
          'Ruby' => 'yarv',
          'Python' => 'python3',
          'PHP' => 'php',
          'Hack' => 'hack',
          'Lua' => 'lua',
          'C' => 'gcc',
          'Golang' => 'go',
          'Ada' => 'gnat',
          'Erlang' => 'hipe'];
$retries = 3;
$metrics = ['Time' => 1,
            'Memory' => 2];

const format = 'http://benchmarksgame.alioth.debian.org/u64q/compare.php?lang=%s&lang2=%s';
function stripComma($node) {
	return str_replace(',', '', trim($node->textContent));
}
function node2num($node) {
	return stripComma($node) + 0;
}

$start = time(); ?>

<!DOCTYPE HTML>
<html>
<head>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
	<style>
		table {
			font-family: arial, sans-serif;
			border-collapse: collapse;
			width: 100%;
		}
		td, th {
			border: 1px solid #dddddd;
			text-align: left;
			padding: 8px;
		}
		tr:nth-child(even) {
			background-color: #dddddd;
		}
		td:first-child {
			font-weight: bold;
		}
	</style>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
</head>
<body>
<h1>Comparison of Benchmark Results for Programming Languages</h1>
<?php foreach ($metrics as $metric => $metricNum) { ?>
	<p><?= $metric ?>: Lang2 / Lang1</p>
	<table>
		<tr>
			<th>Lang 1 vs Lang 2</th>
			<?php foreach (array_keys($langs) as $lang)
				echo "<th>$lang</th>"; ?>
		</tr>
		<?php
		foreach ($langs as $lang1 => $langCode1) {
			echo "<tr><td>$lang1</td>";
			
			foreach ($langs as $lang2 => $langCode2) {
				echo '<td>';
				if ($lang1 == $lang2) {
					echo '1</td>';
					continue;
				}
				
				// get the HTML output from the benchmarking site
				$ch = curl_init(sprintf(format, $langCode1, $langCode2));
				curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); # suppress output
				$html = curl_exec($ch);
				curl_close($ch);
				
				// get the benchmark results for the given metric
				$dom = new DOMDocument();
				@$dom->loadHTML($html); // the HTML for benchmarking site is broken
				
				$sum = $num = 0;
				
				$rowList = $dom->getElementsByTagName('tr');
				for ($i = 0; $i < $rowList->length; $i++) {
					$cols = $rowList->item($i)->getElementsByTagName('td');
					$nextCols = $rowList->item(++$i)->getElementsByTagName('td');
					
					// check that it is a table row, not a header
					if (!$cols->length) {
						$i--;
						continue;
					}
					
					// make sure the result is valid
					if (!is_numeric(stripComma($cols->item(1))) ||
						!is_numeric(stripComma($nextCols->item(1)))) {
						// skip two, since we want to look at lang1 & lang2 results as a pair
						continue;
					}
					
					// collect results
					$result1 = node2num($cols->item($metricNum));
					$result2 = node2num($nextCols->item($metricNum));
					// once we check the other language, we can safely skip the next row
					
					$sum += $result2 / $result1;
					$num++;
				}
				
				// output the mean result into the table
				if ($num) echo round($sum / $num, 2);
				echo '</td>';
			}
			
			echo '</tr>';
		} ?>
	</table>
<?php } ?>
<p>Operation completed in <?= time() - $start ?>s.</p>
<script>
	$(function () {
		$('td').each(function () {
			if ($(this).text() > 3)
				$(this).css('color', 'red')
			else if ($(this).text() < 1/2)
				$(this).css('color', 'green')
			else if (1.5 < $(this).text())
				$(this).css('color', 'orange')
		})
	})
</script>
</body>
</html>