<!DOCTYPE html>
<html>
	<head>
		<title>Site Map Generator</title>
	</head>
	<body>
		<div id="content">
			<h1>Site Map Generator</h1>
				<form action="index.php" method="POST">
					URL: <input name="url" placeholder="http://www.chrispileggi.com"/>
					<input type="submit" name="submit" value="Start Crawling"/>
				</form>
				<?php
				error_reporting(E_ALL);
ini_set('display_errors', '1');
					include("simple_html_dom.php");
					$crawled_urls=array();
					$found_urls=array();
					$cnt = 0;

					function rel2abs($rel, $base)
					{
						// URL is already absolute if there is no scheme
						if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel; // calls url scheme PHP_URL_SCHEME ex. string(4) "http"
						
						// The first character of $rel is not the start of GET variable(s) or an anchor
						if ($rel[0]=='#' || $rel[0]=='?') return $base . $rel;
						
						// Extract parsed URL as seperate variables  (ALWAYS THE SAME??)
						extract(parse_url($base));

						// Remove the last "/" and anything beyond it in the $path variable
						$path = preg_replace('#/[^/]*$#', '', $path);

						// $path is null if the beginning of $rel is a /
						if ($rel[0] == '/') { $path = ''; }

						// Construct the absolute URL ($host is example.com)
						$abs = "$host$path/$rel";

						// /./ or // and /no 2 dots any characters but / dot dot / (ex /jnjnjkn/../)
						$re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');

						// Shorthand for loop; 
						//for($n=1; $n>0;$abs=preg_replace($re,'/', $abs,-1,$n)){echo $abs . " " . ++$count . " " . $n . "<br />";}
						
						// Replace values in string that match array
						$abs=preg_replace($re,'/', $abs);

						$abs=str_replace("../","",$abs);

						// ex: http
						return $scheme.'://'.$abs;
					}

					function perfect_url($u,$b)
					{
						// Original URL in parts
						$bp=parse_url($b);

						// Add scheme and host if URL is not just "/" 
						if($bp['path']!="/")
						{
							if($bp['scheme']==""){$scheme="http";}else{$scheme=$bp['scheme'];}
							$b=$scheme."://".$bp['host']."/";
						}

						// 
						//echo $u . " " . $b;
						if(substr($u,0,2)=="//") { $u="http:".$u; }

						if(substr($u,0,4)!="http") { $u=rel2abs($u,$b); }

						return $u;
					}
function del($url)
{
$file_headers = @get_headers($url);
//echo $file_headers[0];
if(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found' || $file_headers[0] == 'HTTP/1.1 410 Gone') {
    $exists = "na";
}
else {
    $exists = "ya";
}
return $exists;
}
					function crawl_site($u)
					{
						// Obtain global $crawled_urls array
						global $crawled_urls;
						global $found_urls;
						global $nUrl;
						global $cnt;
						echo $cnt;
						$urlParts = parse_url($u);
						
						if (!isset($urlParts["path"])) { $current = "/"; }
						else { $current = $urlParts["path"]; }
						
						$current = $nUrl . "" . $current;
						//$crawled_urls[$current] = 0; 
						// Non alpha-numeric characters replaced
						//$uen=urlencode($u);
						//echo $uen;
						// $uen is not a key in crawled urls array or the date stored for $uen is less that 25 seconds ago
						
						//if((array_key_exists($uen,$crawled_urls)==0 || $crawled_urls[$uen] < date("YmdHis",strtotime('-25 seconds', time()))))
						if (!isset($crawled_urls[$current]))
						{
							if($current!='' && substr($current,0,4)!="mail" && substr($current,0,4)!="java" && del($current) == "ya")
							{
								// Add url as key in found_urls adn give a 1
								$crawled_urls[$current] = 0;
							}
							
						}				
						if (isset($crawled_urls[$current]) && $crawled_urls[$current] == 0 && del($current) == "ya")
						{
							$crawled_urls[$current] = 1;
							// Collect html elements as dom from URL
							

							$html = file_get_html($current);

							// Store encoded url and timestamp
							//$crawled_urls[$uen]=date("YmdHis");

							// The URL does not begin with "mail" or "java" and url does not exist in found_urls array

								// All anchor tags in the $html object
								foreach($html->find("a") as $li)
								{
									// Normalize URL and break as array
									//$url=perfect_url($li->href,$u);
									//$enurl=urlencode($url);
									$urlParts2 = parse_url($li->href);
									$current2 = ($urlParts2["path"] == "" ? "/" : $urlParts2["path"]);
									
									if (substr($current2,0,3)=="../") {$current2 = substr($current2, 2); }
									else if (substr($current2,0,2)=="./")  {$current2 = substr($current2, 1); } 
									else if (substr($current2,0,1)!="/")  { $current2 = "/" . $current2; } 
									
									if($current2!='' && substr($current2,0,4)!="mail" && substr($current2,0,4)!="java")
									{
										// Add url as key in found_urls adn give a 1
										array_push($found_urls, $nUrl . "" . $current2);
										echo $current2 . "<br />";
									}
								}
							}
						}

					if(isset($_POST['submit']))
					{
						$url=$_POST['url'];
						
						if($url=='') { echo "<h3>No URL</h3>";}
						else
						{
							//extract(parse_url($url));
							$urlParts = parse_url($url); 
							//echo "$scheme  s   $host  h  $port p $user u $pass pass $path path  $query  q  $fragment f";
							if (!isset($urlParts['scheme'])) 
							{ 
								$url = "http://" . $url;
								$urlParts = parse_url($url);
							}
							$nUrl = $urlParts["scheme"] . "://" . $urlParts["host"];
							echo "<h2>Result - URL's Found</h2><ul style='word-wrap: break-word;width: 400px;line-height: 25px;'>";
							//array_push($crawled_urls, $nUrl);
							$crawled_urls[$nUrl] = 0;
							crawl_site($nUrl);
							//while (list($key, $value) = each($crawled_urls)) {
								
								//if ($value!=1) {crawl_site($url . $key);}
							//}
							
							$loop = true;
							while ($loop)
							{
								if ($cnt < count($found_urls))
								{
								//echo "b" . count($found_urls) . "e";
									crawl_site($found_urls[$cnt]);
									$cnt++;
								}
								else { $loop = false;}
							}
							
							
							foreach ($crawled_urls as $i => $j)
							{
								echo $i . "<br />";
								//crawl_site($i);
							}
							
							echo "</ul>";
							
							//$xmlMap = fopen("sitemap.xml", "w+");
							//fclose($xmlMap);
							
							//Create XML document
							$xml = new DOMDocument();
							$xml_album = $xml->createElement("urlset");
							$xml_track = $xml->createElement("url");
							$xml_album->appendChild( $xml_track );
							$xml->appendChild( $xml_album );

							$xml->save("sitemap.xml");
							//caching? recursion, like breadthfirst , isset()
						}
					}
				?>
			</div>
			<style>
				input
				{
					border:none;
					padding:8px;
				}
				
				#content
				{
					style="margin-top:10px;
					height:100%;"
				}
			</style>
		</body>
</html>
