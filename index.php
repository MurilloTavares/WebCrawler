<?php
error_reporting(E_ERROR | E_PARSE);

$start = "http://localhost/WebCrawler/page/test.html";

$already_crawled = array();
$crawling = array();

function get_details($url){
    // Mudando Agente
    $options = array('http' => array('method' => "GET", 'headers' => "User-Agent: CrawlerBot/0.1\n"));
    $context = stream_context_create($options);

    // DOMDocument pra faser parse de pagina html
    $doc = new DOMDocument();
    $doc->loadHTML(file_get_contents($url, false, $context));

    $title = $doc->getElementsByTagName("title");
    $title = $title->item(0)->nodeValue;

    $description = "";
    $keywords = "";
    $metas = $doc->getElementsByTagName("meta");
    foreach($metas as $meta){
        if(strtolower($meta->getAttribute("name")) == "description"){
            $description = $meta->getAttribute("content");
        }
        if(strtolower($meta->getAttribute("name")) == "keywords"){
            $keywords = $meta->getAttribute("content");
        }
    }

    // Limpando quebras de linha para nao desformatar saida json
    $title = str_replace("\n", "", $title);
    $description = str_replace("\n", "", $description);
    $keywords = str_replace("\n", "", $keywords);

    return '{ "Titlte": "'.$title
        .'", "Description": "'.$description
        .'", "Keywords": "'.$keywords
        .'", URL: "'.$url.'" },';

}

function follow_links($url) {

    global $already_crawled;
    global $crawling;

    // Mudando Agente
    $options = array('http' => array('method' => "GET", 'headers' => "User-Agent: CrawlerBot/0.1\n"));
    $context = stream_context_create($options);

    // DOMDocument pra faser parse de pagina html
    $doc = new DOMDocument();
    $doc->loadHTML(file_get_contents($url, false, $context));

    // Listando Links
    $linklist = $doc->getElementsByTagName("a");
    foreach($linklist as $link){
        
        $l = $link->getAttribute("href");

        // Transformando href para url completa

        // //youtube.com ---> https://youtube.com
        if (substr($l, 0, 2) == "//"){
            $l = parse_url($url)["scheme"].":".$l;
        } 

        // /ajuda.html ---> http://localhost/ajuda.html
        else if (substr($l, 0, 1) == "/") {
            $l = parse_url($url)["scheme"]."://".parse_url($url)["host"].$l;
        }

        // ./exemplo.html ---> http://localhost/page/exemplo.html
        else if (substr($l, 0, 2) == "./") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"].dirname(parse_url($url)["path"]).substr($l, 1);
        }
        
        // #anchor ---> http://exemplo.com/doc.html#anchor
        else if (substr($l, 0, 1) == "#") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"].parse_url($url)["path"].$l;
        }
        
        // ../../outraPagina.html ---> http://localhost/../../outraPagina.html
        else if (substr($l, 0, 3) == "../") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"]."/".$l;
        }
        
        // Caso href=javascrip ignore
        else if (substr($l, 0, 11) == "javascript:") {
			continue;
        }

        // index.html ---> http://exemplo.com/index.html
        else if (substr($l, 0, 5) != "https" && substr($l, 0, 4) != "http") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"]."/".$l;
		}

        // Adicionando link ao array caso nao exista
        if(!in_array($l, $already_crawled)){
            $already_crawled[] = $l;
            $crawling[] = $l;
            echo get_details($l)."\n";
        }
    }
}

follow_links($start);


// Buscando links recursivamente
array_shift($crawling);
foreach($crawling as $newLink){
    follow_links($newLink);
}

print_r($already_crawled);

?>