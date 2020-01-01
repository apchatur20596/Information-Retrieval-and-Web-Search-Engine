<?php
include 'SpellCorrector.php';
include 'simple_html_dom.php';
header('Content-Type: text/html; charset=utf-8');
$div=false;
$correct = "";
$correct1="";
$output = "";
$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$results = false;

if ($query)
{
  $choice = isset($_REQUEST['sort'])? $_REQUEST['sort'] : "default";

  require_once('Apache/Solr/Service.php');

  $solr = new Apache_Solr_Service('localhost', 8983, '/solr/myexample3');

  if (get_magic_quotes_gpc() == 1)
  {
    $query = stripslashes($query);
  }
  try
  {
    if($choice == "default")
     $additionalParameters=array('sort' => '');
   else{
    $additionalParameters=array('sort' => 'pageRankFile desc');
  }
  $word = explode(" ",$query);

  $spell = $word[sizeof($word)-1];
  for($i=0;$i<sizeOf($word);$i++){
    ini_set('memory_limit',-1);
    ini_set('max_execution_time', 300);
    $che = SpellCorrector::correct($word[$i]) ;

    if($correct!="")$correct = $correct."+".trim($che);
    else{
      $correct = trim($che);}
      $correct1 = $correct1." ".trim($che);
    }
    $correct1 = str_replace("+"," ",$correct);
    $div=false;
    if(strtolower($query)==strtolower($correct1)){
      $results = $solr->search($query, 0, $limit, $additionalParameters);
    }
    else {
      $div =true;
      $results = $solr->search($query, 0, $limit, $additionalParameters);
      $link = "http://localhost/solr-php-client/myscript.php?q=$correct&sort=$choice";
      $output = "Did you mean: <a href='$link'>$correct1</a>";
    }
  }
  catch (Exception $e)
  {
    die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
  }
}

?>
<html>
<head>
  <title>Homework 5</title>
  <link rel="stylesheet" href="http://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
  <script src="http://code.jquery.com/jquery-1.10.2.js"></script>
  <script src="http://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
</head>
<body>
  <form  accept-charset="utf-8" method="get">
    <label for="q">Search:</label>
    <input id="q" name="q" type="text" value="<?php $input = htmlspecialchars($query, ENT_QUOTES, 'utf-8');echo $input; ?>"/>
    <input type="submit" value="Submit"/>
    <br/>

    <input type="radio" name="sort" value="pagerank" <?php if(isset($_REQUEST['sort']) && $choice == "pagerank") { echo 'checked="checked"';} ?>>Page Rank
    <input type="radio" name="sort" value="default" <?php if(isset($_REQUEST['sort']) && $choice == "default") { echo 'checked="checked"';} ?>>Default
  </form>
  <script>
   $(function() {
     var URL_PREFIX = "http://localhost:8983/solr/myexample3/suggest?q=";
     var URL_SUFFIX = "&wt=json&indent=true";
     var count=0;
     var tags = [];
     $("#q").autocomplete({
       source : function(request, response) {
         var correct="",before="";
         var query = $("#q").val().toLowerCase();
         var character_count = query.length - (query.match(/ /g) || []).length;
         var space =  query.lastIndexOf(' ');
         if(query.length-1>space && space!=-1){
          correct=query.substr(space+1);
          before = query.substr(0,space);
        }
        else{
          correct=query.substr(0); 
        }
        var URL = URL_PREFIX + correct+ URL_SUFFIX;
        $.ajax({
         url : URL,

         success : function(data) {
          var js =data.suggest.suggest;
          var docs = JSON.stringify(js);
          var jsonData = JSON.parse(docs);
          var result =jsonData[correct].suggestions;
          var j=0;
          var stem =[];
          for(var i=0;i<5 && j<result.length;i++,j++){
            if(result[j].term==correct)
            {
              i--;
              continue;
            }
            for(var k=0;k<i && i>0;k++){

              if(tags[k].indexOf(result[j].term) >=0){
                i--;
                continue;
              }
            }
            if(result[j].term.indexOf('.')>=0 || result[j].term.indexOf('_')>=0)
            {
              i--;
              continue;
            }
            var s =(result[j].term);
            if(stem.length == 5)
              break;
            if(stem.indexOf(s) == -1)
            {
              stem.push(s);
              if(before==""){
                tags[i]=s;
              }
              else
              {
                tags[i] = before+" ";
                tags[i]+=s;
              }
            }
          }
          console.log(tags);
          response(tags);
        },
        dataType : 'jsonp',
        jsonp : 'json.wrf'
      });
      },
      minLength : 1
    })
   });
 </script>
 <?php
 if($div){
  echo $output;
}
$count =0;
$prev="";
$arrayFromCSV =  array_map('str_getcsv', file('data.csv'));
if ($results)
{
  $total = (int) $results->response->numFound;
  $start = min(1, $total);
  $end = min($limit, $total);
  echo "  <div>Results $start -  $end of $total :</div> <ol>";
  foreach ($results->response->docs as $doc)
  {  
    $id = $doc->id;
    $title = $doc->title;
    $desc = $doc->description;
    if($title=="" ||$title==null){
     $title = $doc->dc_title;
     if($title=="" ||$title==null)
       $title="N/A";
   }
   $id2 = $id;
   $id = str_replace("/Users/jeetmody/solr-6.5.0/NBCNewsDownloadData/","",$id);
   foreach($arrayFromCSV as $row1)
   {
    if($id==$row1[0])
    {
      $url = $row1[1];
      break;
    }
  }
  $searchfor = $_GET["q"];
  $ary = explode(" ",$searchfor);
  $count = 0;
  $max = 0;
  $finalSnippet = "";
  $HtmlText = substr($id,0,strlen($id)-5);

  $html_to_text_files_dir = "/Library/WebServer/Documents/solr-php-client/parsed/";
  $file_name = $html_to_text_files_dir . $HtmlText;
  $file = fopen($file_name,"r");
  while(! feof($file))
  {

    $snippet = fgets($file);
    $elementlower = strtolower($snippet);
    foreach($ary as $wd)
    {
      $wd = strtolower($wd);
      if (strpos($elementlower, $wd) !== false) 
      {
        $count = $count+1;
      }
    }
    if($max<$count)
    {
      $finalSnippet = $snippet;
      $max = $count;
    }
    else if($max==$count && $count>0)
    {
      if(strlen($finalSnippet)<strlen($snippet))
      {
        $finalSnippet = $snippet;
        $max = $count;
      }
    }
    $count = 0;
  }
  $pos = 0;
  $wd = "";
  foreach ($ary as $wd) {
    if (strpos(strtolower($finalSnippet), strtolower($wd)) !== false) 
    {
      $pos = strpos(strtolower($finalSnippet), strtolower($wd));

      break;
    }
  }
  $start = 0;
  if($pos>80)
  {
    $start = $pos - 80; 
  }
  else
  {
    $start = 0;
  }

  $end = $start + 160;
  if(strlen($finalSnippet)<$end)
  {
    $end = strlen($finalSnippet)-1;

    $post1 = "";
  }
  else
  {
    $post1 = "...";
  }
  
  if(strlen($finalSnippet)>160)
  {
    if($start>0)
      $pre = "...";
    else
      $pre = "";
    
    $finalSnippet = $pre . substr($finalSnippet,$start,$end-$start+1) . $post1;
  }

  if(strlen($finalSnippet)==0)
  {
    $finalSnippet = $desc;
  }


  fclose($file);

  unset($row1);
  error_reporting(E_ALL ^ E_NOTICE);  
  echo "  <li><a href='$url' target='_blank'>$title</a></br>
  <a href='$url' target='_blank'>$url</a></br>
  <b>Description:</b> $desc<br/>
  <b>Snippet:</b> $finalSnippet<br/>
  <b>ID: </b> $id2</li></br></br>";
  array_push($stack,$id2);
}
echo "</ol>";
}
?>

</body>
</html>