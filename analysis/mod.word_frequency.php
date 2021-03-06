<?php
require_once './common/config.php';
require_once './common/functions.php';

$lowercase = isset($_GET['lowercase']) ? $lowercase = $_GET['lowercase'] : 0;
$minf = isset($_GET['minf']) ? $minf = $_GET['minf'] : 1;

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>Twitter Tool</title>

        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

        <link rel="stylesheet" href="css/main.css" type="text/css" />

        <script type="text/javascript" language="javascript">
	
	
	
        </script>

    </head>

    <body>

        <h1>Twitter Analytics - Word frequency</h1>

        <?php
        validate_all_variables();

        $tempfile = tmpfile();
        fputs($tempfile, chr(239) . chr(187) . chr(191));

        mysql_query("set names utf8");
        $sql = "SELECT text, " . sqlInterval() . " FROM " . $esc['mysql']['dataset'] . "_tweets t ";
        $sql .= sqlSubset();
        //$sql .= " GROUP BY datepart ORDER BY datepart ASC";
        $sql .= " ORDER BY datepart ASC";
        $sqlresults = mysql_query($sql);
        $debug = '';
        if ($sqlresults) {
            while ($data = mysql_fetch_assoc($sqlresults)) {
                $text = validate($data["text"], "tweet");
                $datepart = str_replace(' ', '_', $data["datepart"]);
                preg_match_all('/(https?:\/\/[^\s]+)|([@#\p{L}][\p{L}]+)/u', $text, $matches, PREG_PATTERN_ORDER);
                foreach ($matches[0] as $word) {
                    if (preg_match('/(https?:\/\/)/u', $word)) continue;
                    if ($lowercase !== 0) $word = mb_strtolower($word);
                    fputs($tempfile, "\"$datepart\" \"$word\"\n");
                }
            }
        }

        function cleanText($text) {
            return preg_replace("/[\r\t\n,]/", " ", addslashes(trim(strip_tags(html_entity_decode($text)))));
        }

        if (function_exists('eio_fsync')) { eio_fsync($tempfile); }
                                     else { fflush($tempfile); }

        $tempmeta = stream_get_meta_data($tempfile);
        $templocation = $tempmeta["uri"];

        // write csv results

        $filename = get_filename_for_export("wordFrequency");
        $csv = fopen($filename, "w");
        fputs($csv, chr(239) . chr(187) . chr(191));
        fputs($csv, "interval,word,frequency\n");
        system("sort -S 8% $templocation | uniq -c | sort -S 8% -b -k 2,2 -k 1,1nr -k 3,3 | awk '{ if ($1 >= $minf) { print $2 \",\" $3 \",\" $1} }' | sed -e 's/_/ /' >> $filename");
 
        fclose($csv);
        
        fclose($tempfile); // this removes the temporary file

        echo '<fieldset class="if_parameters">';
        echo '<legend>Your File</legend>';
        echo '<p><a href="' . filename_to_url($filename) . '">' . $filename . '</a></p>';
        echo '</fieldset>';
        ?>

    </body>
</html>
