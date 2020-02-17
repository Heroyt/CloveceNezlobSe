<?php

define('POLE', 48);
define('PLAYER', 'human_player');
define('AI', 'ai_player');
define('SAFE_WEIGTH', 0.7);
define('PAUSE', 0.3*1000000);

echo "\033[1m"; // ALL BOLD

$content = '';

$title = file_get_contents('clovece-title.txt');
$field = file_get_contents('clovece-field.txt');

$content .= $title;

// MENU
$content .= "\n\n\n(WHITE)MENU";
$content .= "\n\n(WHITE)1 - Začít hrát\n";
$content .= "(WHITE)2 - Ukončit\n\n\n\n";

display($content);

$a = (integer) readline("Volba: ");
while (trim($a) === '' && $a !== 1 && $a !== 2) {
	$a = (integer) readline("Špatná volba, vyberte prosím správnou volbu (1,2): ");
}

var_dump($a);

if ($a === 1 || trim($a) === '' || $a === 0) { // START GAME

	$players = [
		1 => AI,
		2 => AI,
		3 => AI,
		4 => AI
	];

	$content = $title;

	display($content);

	$a = readline("\n\nPočet hráčů (1-4): ");
	while ($a < 0 || $a > 4) {
		$a = readline("\n\nChybný počet hráčů, zadejte prosím správný počet (1-4): ");
	}
	for ($i=1; $i <= $a; $i++) {
		$players[$i] = PLAYER;
	}

	$figures = [
		1 => ['d', 'd', 'd', 'd'],
		2 => ['d', 'd', 'd', 'd'],
		3 => ['d', 'd', 'd', 'd'],
		4 => ['d', 'd', 'd', 'd']
	];

	$dom = [
		1 => ['(FIG-1)','(FIG-1)','(FIG-1)','(FIG-1)'],
		2 => ['(FIG-2)','(FIG-2)','(FIG-2)','(FIG-2)'],
		3 => ['(FIG-3)','(FIG-3)','(FIG-3)','(FIG-3)'],
		4 => ['(FIG-4)','(FIG-4)','(FIG-4)','(FIG-4)']
	];
	$fin = [
		1 => [1 => ' ', 2 => ' ', 3 => ' ', 4 => ' '],
		2 => [1 => ' ', 2 => ' ', 3 => ' ', 4 => ' '],
		3 => [1 => ' ', 2 => ' ', 3 => ' ', 4 => ' '],
		4 => [1 => ' ', 2 => ' ', 3 => ' ', 4 => ' ']
	];
	$state = [];
	$startPlaces = [1 => 1, 2 => 13, 3 => 25, 4 => 37];
	$endPlaces = [1 => 47, 2 => 11, 4 => 35, 3 => 23];

	for ($i=0; $i < POLE; $i++) {
		$state[] = ' ';
	}

	$win = false;
	$playing = 1;

	while ($win === false) {

		$content = $title."(WHITE)\n\n\n".$field;
		display($content);

		echo "\n\nHraje hráč: $playing\n\n";

		if ($players[$playing] === PLAYER) {
			echo "1 - Hodit kostkou\n";
			echo "2 - Konec\n\n";

			$a = (int) readline("Volba: ");
			while (trim($a) === '' && $a !== 1 && $a !== 2) {
				$a = (int) readline("Špatná volba, vyberte prosím správnou volbu (1,2): ");
			}

			if ($a === 2) break;

		}

		$dice = rand(1, 6);
		$sum = $dice;

		echo "Na kostce padlo: $dice\n";


		if ($players[$playing] === PLAYER) {
			readline("Pokračovat? (ENTER)");
		}
		else {
			usleep(PAUSE);
		}

		$out = array_filter($figures[$playing], function($a){return gettype($a) === 'integer' || gettype($a) === 'array';});
		$outWOFin = array_filter($figures[$playing], function($a){return gettype($a) === 'integer';});
		$domecek = array_filter($figures[$playing], function($a){return $a === 'd';});

		// POSUNUTÍ ZE STARTU
		if (count($domecek) > 0 && $dice === 6 && $state[$startPlaces[$playing]] !== '(FIG-'.$playing.')') {
			if ($players[$playing] === PLAYER) {
				$a = readline("Můžete posunout figurku ze startu (a/n)");
				while (trim($a) === '' && $a !== 'a' && $a !== 'n') {
					$a = readline("Špatná volba, vyberte prosím správnou volbu (a,n): ");
				}
				if ($a === 'a') {
					$key = array_search('d', $figures[$playing]);
					$f = array_shift($dom[$playing]);
					$dom[$playing][] = ' ';
					collisionCheck($startPlaces[$playing]);
					$state[$startPlaces[$playing]] = $f;
					$figures[$playing][$key] = $startPlaces[$playing];
					continue;
				}
			}
			else {
				if (count($outWOFin) === 0) { // IF NO OTHER FIGURES ARE OUT, ALWAYS MOVE OUT
					$key = array_search('d', $figures[$playing]);
					$f = array_shift($dom[$playing]);
					$dom[$playing][] = ' ';
					collisionCheck($startPlaces[$playing]);
					$state[$startPlaces[$playing]] = $f;
					$figures[$playing][$key] = $startPlaces[$playing];
					continue;
				}
				$weights = weightPole();
				$moves = [];
				foreach ($out as $fig) {
					if (gettype($out) !== 'integer') continue;
					$a = $fig+6;
					if ($a >= $endPlaces[$playing] || !validMoveCheck($a)) continue;
					if ($a >= POLE) $a -= POLE;
					$moves[$fig] = $weights[$a];
				}
				if (count($moves) > 0 && $weights[$startPlaces[$playing]] >= max($moves)) { // IF NO OTHER MOVE IS MORE BENEFFICIAL TO MAKE, GET OUT
					$key = array_search('d', $figures[$playing]);
					$f = array_shift($dom[$playing]);
					$dom[$playing][] = ' ';
					collisionCheck($startPlaces[$playing]);
					$state[$startPlaces[$playing]] = $f;
					$figures[$playing][$key] = $startPlaces[$playing];
					continue;
				}
			}
		}

		// POHYB
		if (count($out) > 0) {
			// THROW AGAIN
			while ($dice === 6) {
				if ($players[$playing] === PLAYER) {
					$a = readline("Můžete hodit znovu (a/n)");
					while (trim($a) === '' || ($a !== 'a' && $a !== 'n')) {
						$a = readline("Špatná volba, vyberte prosím správnou volbu (a,n): ");
					}
					if ($a === 'n') break;
				}
				$dice = rand(1, 6);
				$sum += $dice;
				echo "Na kostce padlo: $dice (celkem: $sum)\n";
				if ($players[$playing] === PLAYER) readline("Pokračovat? (ENTER)");
				else usleep(PAUSE);
			}

			// POSSIBLE MOVES
			$keys = [];
			foreach ($out as $key => $num) {
				$nextP = (gettype($num) === 'array' ? $num[0] : $num) + $sum;
				if ($num <= $endPlaces[$playing] && $nextP > $endPlaces[$playing]+4) continue; // WOULD REACH TOO FAR OUT
				if (gettype($num) === 'array') {
					if (!validMoveCheck([$nextP])) continue;
					if ($players[$playing] === PLAYER) $fin[$playing][$num[0]] = '(FIGCHOOSE-'.$playing.'-'.($key+1).')';
				}
				else {
					if (!validMoveCheck($nextP, $num)) continue;
					if ($nextP >= POLE) $nextP -= POLE;
					if ($players[$playing] === PLAYER) $state[$num] = '(FIGCHOOSE-'.$playing.'-'.($key+1).')';
				}
				$keys[] = $key;
			}

			if (count($keys) <= 0) {
				// CHECK WINNING
				$final = array_filter($figures[$playing], function($a){return gettype($a) === 'array';});
				if (count($final) >= 4) {
					display($content);
					echo "\n\n\033[35m VÍTĚTSTVÍ: HRÁČ $playing\033[0m\n";
					$win = true;
					break;
				}
				$playing++;
				if ($playing > 4) $playing = 1;
				continue;
			}

			// ONLY ONE MOVE AVAILABLE
			if (count($keys) === 1) {
				$f = reset($keys);
			}
			else { // FIGURE CHOOSE
				display($content);
				echo "\n\nHraje hráč: $playing\n\n";
				if ($players[$playing] === PLAYER) {
					$f = readline("Vyberte figurku: ");
					if (empty($f)) $f = reset($keys)+1;
					while (!in_array($f-1, $keys)) {
						$f = readline("Tato figurka není na výběr, vyberte jinou: ");
					}
					$f--;
				}
				else {
					$weights = weightPole();
					$moves = [];
					foreach ($keys as $key) {
						$fig = $out[$key];
						if (gettype($fig) === 'integer') {
							$a = $fig + $sum;
							if ($a >= $endPlaces[$playing]) {
								$moves[$key] = SAFE_WEIGTH - $weights[$fig];
							}
							if ($a >= POLE) $a -= POLE;
							$moves[$key] = $weights[$a] - $weights[$fig];
						}
						else {
							$moves[$key] = 0.1;
						}
						$f = array_search(max($moves),$moves);
					}
				}
			}

			$num = $out[$f];
			$nextP = (gettype($num) === 'array' ? $num[0] : $num) + $sum;
			$moveToFin = false;
			if ($num <= $endPlaces[$playing] && $nextP > $endPlaces[$playing]) {
				$moveToFin = true;
				$nextP -= $endPlaces[$playing];
			}
			elseif ($nextP >= POLE) $nextP -= POLE;

			if ($moveToFin || gettype($num) === 'array') {
				if (gettype($num) === 'array') $fin[$playing][$num[0]] = ' ';
				else $state[$num] = ' ';
				$figures[$playing][array_search($num, $figures[$playing])] = [$nextP];
				$fin[$playing][$nextP] = '(FIG-'.$playing.')';
			}
			else {
				collisionCheck($nextP);
				$state[(gettype($num) === 'array' ? $num[0] : $num)] = ' ';
				$figures[$playing][array_search($num, $figures[$playing])] = $nextP;
			}
			$out = array_filter($figures[$playing], function($a){return gettype($a) === 'integer' || gettype($a) === 'array';});
			// print_r($out);
			foreach ($out as $n) {
				if (gettype($n) === 'array') {
					$fin[$playing][$n[0]] = '(FIG-'.$playing.')';
				}
				else {
					$state[$n] = '(FIG-'.$playing.')';
				}
			}

		}

		// CHECK WINNING
		$final = array_filter($figures[$playing], function($a){return gettype($a) === 'array';});
		if (count($final) >= 4) {
			display($content);
			echo "\n\n\033[35m VÍTĚTSTVÍ: HRÁČ $playing\033[0m\n";
			$win = true;
			// print_r($figures);
			break;
		}

		$playing++;
		if ($playing > 4) $playing = 1;

	}

	echo "\n\nKONEC\n";

}
else {
	echo "\n\nKONEC\n";
}

echo "\033[0m"; // RESET TO DEFATULT CONSOLE TEXT

// PARSING FUNCTION
function display($content) {
	global $figures, $dom, $state, $fin;

	if (isset($dom[1]) > 0)rsort($dom[1]);
	if (isset($dom[2]) > 0)rsort($dom[2]);
	if (isset($dom[3]) > 0)rsort($dom[3]);
	if (isset($dom[4]) > 0)rsort($dom[4]);

	system('clear');

	// PARSE COLORS
	$content = str_replace("(RED)", "\033[31m", $content);
	$content = str_replace("(GREEN)", "\033[32m", $content);
	$content = str_replace("(YELLOW)", "\033[33m", $content);
	$content = str_replace("(BLUE)", "\033[34m", $content);
	$content = str_replace("(MAGENTA)", "\033[35m", $content);
	$content = str_replace("(CYAN)", "\033[36m", $content);
	$content = str_replace("(WHITE)", "\033[37m", $content);
	$content = str_replace("(GREY)", "\033[0:37m", $content);

	// PARSE START
	$content = preg_replace_callback("/(\(DOM-[0-9]-[0-9]\))/", function($a) {
		global $dom;
		$args = explode('-', $a[0]);
		$col = '';
		switch ((int) $args[1]) {
			case 1:
				$col = "\033[41m";
				break;
			case 2:
				$col = "\033[42m";
				break;
			case 3:
				$col = "\033[43m";
				break;
			case 4:
				$col = "\033[44m";
				break;
		}
		return $col.'('.$dom[$args[1]][substr($args[2],0,1)-1].$col.")\033[0m";
	}, $content);

	// PARSE DOMEČKY
	$content = preg_replace_callback("/(\(FIN-[0-9]-[0-9]\))/", function($a) {
		global $fin;
		$args = explode('-', $a[0]);
		$col = '';
		switch ((int) $args[1]) {
			case 1:
				$col = "\033[41m";
				break;
			case 2:
				$col = "\033[42m";
				break;
			case 3:
				$col = "\033[43m";
				break;
			case 4:
				$col = "\033[44m";
				break;
		}
		return $col.'('.$fin[$args[1]][substr($args[2],0,1)].$col.")\033[0m";
	}, $content);

	// PARSE POLE
	$content = preg_replace_callback("/(\(POLE-[0-9][0-9]\))/", function($a) {
		global $state, $startPlaces;
		$args = explode('-', $a[0]);
		$num = (int) substr($args[1], 0, 2);
		$col = '';
		$s = array_search($num, $startPlaces);
		if ($s !== false) {
			switch ($s) {
				case 1:
					$col = "\033[41m";
					break;
				case 2:
					$col = "\033[42m";
					break;
				case 3:
					$col = "\033[43m";
					break;
				case 4:
					$col = "\033[44m";
					break;
			}
		}
		return $col.'('.$state[$num].$col.")\033[0m";
	}, $content);

	// PARSE FIGURKY
	$content = preg_replace_callback("/(\(FIG-[0-9]\))/", function($a) {
		$args = explode('-', $a[0]);
		$num = (int) substr($args[1], 0, 1);
		switch ($num) {
			case 1:
				$return = "\033[41m";
				break;
			case 2:
				$return = "\033[42m";
				break;
			case 3:
				$return = "\033[43m";
				break;
			case 4:
				$return = "\033[44m";
				break;
		}
		$return .= "X\033[0m";
		return $return;
	}, $content);

	// PARSE FIGURKY - VÝBĚR
	$content = preg_replace_callback("/(\(FIGCHOOSE-[0-9]-[0-9]\))/", function($a) {
		$args = explode('-', $a[0]);
		$num = (int) $args[1];
		$key = (int) substr($args[2],0,1);
		switch ($num) {
			case 1:
				$return = "\033[41m";
				break;
			case 2:
				$return = "\033[42m";
				break;
			case 3:
				$return = "\033[43m";
				break;
			case 4:
				$return = "\033[44m";
				break;
		}
		$return .= "$key\033[0m";
		return $return;
	}, $content);

	echo $content;

	// print_r($figures);
	// print_r($state);
	// print_r($fin);
}

function collisionCheck($num){
	global $state, $dom, $figures;

	if ($state[$num] !== ' ') {
		$f = $state[$num];
		$state[$num] = ' ';
		$p = (int) substr(explode('-', $f)[1], 0, 1);
		// print_r(['p' => $p]);
		// print_r(array_search($num, $figures[$p]));
		// print_r($figures[$p][array_search($num, $figures[$p])]);
		$figures[$p][array_search($num, $figures[$p])] = 'd';
		array_unshift($dom[$p], $f);
		array_pop($dom[$p]);
		// print_r($figures[$p]);
		// print_r($dom[$p]);
		// usleep(PAUSE0);
	}
}
function validMoveCheck($num, $prev = 50) {
	global $playing, $figures, $endPlaces;

	if (
		gettype($num) === 'integer' &&
		$prev <= $endPlaces[$playing] &&
		$num > $endPlaces[$playing] &&
		$num <= $endPlaces[$playing]+4
	) $num = [($num - $endPlaces[$playing])];

	if (gettype($num) === 'array' && $num[0] > 4) return false;

	// print_r([$num, $prev, $figures[$playing]]);

	return !in_array($num, $figures[$playing]);
}

function weightPole(){
	global $playing, $state, $figures, $startPlaces;

	$weights = [];
	for ($i=0; $i < POLE; $i++) {
		$weights[] = 0;
	}

	foreach ($figures as $player => $figs) {
		if ($player === $playing) continue;
		foreach ($figs as $fig) {
			if (gettype($fig) !== 'integer') continue;
			for ($i=1; $i < 6; $i++) {
				$a = $fig-$i;
				if ($a < 0) $a += POLE;
				$weights[$a] += 1/6;
			}
			$weights[$fig] += 1;
			for ($i=1; $i < 6; $i++) {
				$a = $fig+$i;
				if ($a >= POLE) $a -= POLE;
				$weights[$a] -= 1/6;
			}
		}
	}
	foreach ($startPlaces as $player => $num) {
		if ($player === $playing) continue;
		for ($i=0; $i < 6; $i++) {
			$a = $num+$i;
			if ($a >= POLE) $a -= POLE;
			$weights[$a] -= 1/6;
		}
	}

	return $weights;

}

?>
