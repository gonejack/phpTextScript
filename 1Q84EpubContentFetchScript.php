<?php

//Download
$urlTemplate = 'http://cunshang.net/book/1q84/%s.htm';
$range = range(1, 79);

foreach ($range as $i) {
	$url = sprintf($urlTemplate, $i);
	$fileContent = file_get_contents($url);
	$fileName = "html/" . basename($url);

	file_put_contents($fileName, $fileContent);
}

//Parse
foreach (glob('html/*.htm', GLOB_BRACE) as $htmlFile) {
	$html = iconv('gbk', 'utf-8', file_get_contents($htmlFile));
	preg_match('@<TABLE bgcolor.+</table>@mis', $html, $match);

	$liteHtml = strip_tags($match[0], '<br>');
	file_put_contents('liteHtml/' . basename($htmlFile), $liteHtml);
}

//Sanitize
foreach (glob('liteHtml/*.htm', GLOB_BRACE) as $liteHtmlFile) {
	$rawLines = explode('<br>', file_get_contents($liteHtmlFile));

	$sanitizedLines = array();
	foreach ($rawLines as $rawLine) {
		$sanitizedLine = trim(filter_var($rawLine, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW));
		$sanitizedLine && $sanitizedLines[] = $sanitizedLine;
	}

	$txtFileName = basename($liteHtmlFile, 'htm') . 'txt';
	file_put_contents("txt/$txtFileName", implode("\r\n", $sanitizedLines));
}

//Generation
$bookIndex = 1;
$chapterIndex = 0;
$chapterLinks = array();

foreach ($range as $index) {
	$chapterIndex++;

	$txtLines = explode("\r\n", file_get_contents("txt/$index.txt"));
	$htmlLines = array();
	foreach ($txtLines as $txtLine) {
		if ($txtLine) {
			$htmlLine = htmlspecialchars($txtLine);
			$htmlLine = "<p>$htmlLine</p>";
			$htmlLines[] = $htmlLine;
		}
	}
	//head adjust
	$title = strip_tags($txtLines[0]);
	$txtLines[0] = "<h3>$title</h3>";

	$htmlFileName = "chapter{$bookIndex}_$chapterIndex.html";

	$chapterLinks[] = "<p><a href=\"../Text/$htmlFileName\">$title</a></p>";

	file_put_contents("finalHtml/$htmlFileName", implode("\r\n", $txtLines));

	if (in_array($index, array(24, 48))) {

		file_put_contents("finalHtml/chapter{$bookIndex}_0.html", implode("\r\n", $chapterLinks));

		$bookIndex++;
		$chapterIndex = 0;
		$chapterLinks = array();
	}
}