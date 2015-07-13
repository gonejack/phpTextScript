<?php
/**
 * Created by PhpStorm.
 * User: Youi
 * Date: 7/13/2015
 * Time: 2:19 PM
 * Purpose: try to make html for 1Q84 epub book
 */

$g_url_template = 'http://cunshang.net/book/1q84/%s.htm';
$g_range        = range(1, 79);

main();

function main() {
    download();
    parse();
    sanitize();
    final_html_generation();
}

#Download
function download() {
    global $g_range, $g_url_template;

    foreach ($g_range as $i) {
        $url          = sprintf($g_url_template, $i);
        $file_content = file_get_contents($url);
        $file_name    = "html/" . basename($url);

        file_put_contents($file_name, $file_content);

        echo "$url downloaded saved as $file_name.\n";
    }
}

#Parse
function parse() {
    foreach (glob('html/*.htm', GLOB_BRACE) as $html_file) {
        $html = mb_convert_encoding(file_get_contents($html_file), 'utf-8', 'gbk');
        preg_match('@<table bgcolor.+</table>@si', $html, $match);

        $lite_html = strip_tags($match[0], '<br>');
        file_put_contents('liteHtml/' . basename($html_file), $lite_html);

        echo "$html_file parsed.\n";
    }
}

#Sanitize
function sanitize() {
    foreach (glob('liteHtml/*.htm', GLOB_BRACE) as $lite_html_file) {
        $raw_lines = explode('<br>', file_get_contents($lite_html_file));

        $sanitized_lines = array();
        foreach ($raw_lines as $raw_line) {
            $sanitized_line = filter_var($raw_line, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
            $sanitized_line = html_entity_decode(html_entity_decode($sanitized_line)); #twice, in case of something like this troublesome:&amp;nbsp;

            $sanitized_line = str_replace(array("\xc2\xa0", "　"), ' ', $sanitized_line); #for No break spaces which is \xc2\xa0 in utf-8 and chinese space
            $sanitized_line = trim($sanitized_line);

            $sanitized_line && $sanitized_lines[] = $sanitized_line;
        }

        $txt_file_name = basename($lite_html_file, 'htm') . 'txt';
        file_put_contents("txt/$txt_file_name", implode("\r\n", $sanitized_lines));

        echo "$txt_file_name generated.\n";
    }
}

#Generation
function final_html_generation() {
    global $g_range;

    $book_index    = 1;
    $chapter_index = 0;
    $chapter_links = array();

    foreach ($g_range as $index) {
        $chapter_index++;

        $txt_lines  = explode("\r\n", file_get_contents("txt/$index.txt"));
        $html_lines = array();
        foreach ($txt_lines as $txt_line) {
            if ($txt_line) {
                $html_line    = htmlspecialchars($txt_line);
                $html_line    = "<p>$html_line</p>";
                $html_lines[] = $html_line;
            }
        }
        #head adjust
        $title         = strip_tags($html_lines[0]);
        $html_lines[0] = "<h3>$title</h3>";

        $html_file_name = "chapter{$book_index}_$chapter_index.html";

        $chapter_links[] = "<p><a href=\"../Text/$html_file_name\">$title</a></p>";

        file_put_contents("finalHtml/$html_file_name", implode("\r\n", $html_lines));

        echo "book $book_index chapter $chapter_index generated.\n";

        if (in_array($index, array(24, 48, 79))) {

            file_put_contents("finalHtml/chapter{$book_index}_0.html", implode("\r\n", $chapter_links));
            echo "book $book_index chapter 0 generated.\n";

            $book_index++;
            $chapter_index = 0;
            $chapter_links = array();
        }
    }

}