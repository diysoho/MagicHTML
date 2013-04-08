<?php
require_once("phpbrowscap.php");
use phpbrowscap\Browscap;
	class MagicHTML {
		//Conteúdo html
		protected $content;
		//Definindo Variáveis Responsáveis pelo armazenamento de CSS's
		public $css_linked;
		public $css_inline;
		//Definindo Variáveis Responsáveis pelo armazenamento de JS's
		public $js_linked;
		public $js_inline;
		//Definindo Variáveis Responsáveis pelo gerenciamento de metainformações
		protected $title;
		protected $metas;
		protected $body;
		protected $site_container;
		protected $site_container_class;
		protected $doctype;
		protected $html_lang;
		protected $body_id;
		protected $body_class;

		//Definindo outras Variáveis
		protected $requires;
		protected $datamgr;
		protected $output;
		protected $browser;
		//Definindo Variáveis de tratamento de erro
		protected $errors;
		protected $warnings;
		//Path Variables
		protected $base_cache = "/MagicHTML/cache";
		protected $path_cache = "";
		protected $base_css = "/MagicHTML/css";
		protected $path_css = "";







		public function __construct(){
			
			$this->css_inline = array();
			$this->css_linked = array();
			$this->js_linked = array();
			$this->js_inline = array();
			$this->title = "";
			$this->metas = array("charset"=>"charset='utf8'");
			$this->body = "";
			$this->body_id = "page";
			$this->body_class = "";
			$this->site_container_class = "";
			$this->site_container = "all-site";
			$this->doctype = "<!DOCTYPE HTML>";
			$this->html_lang = "pt-br";
			$this->requires = array();
			$this->errors = array();
			$this->warnings = array();
			$this->path_css = $_SERVER['DOCUMENT_ROOT'].$this->base_css;
			$this->path_cache = $_SERVER['DOCUMENT_ROOT'].$this->base_cache;
			if(!is_dir($this->path_cache) or !is_writable($this->path_cache)){
				die("Check path_cache variable");
			}
			if(!is_dir($this->path_css)){
				die("Check path_css variable");
			}
			

			$browser = new Browscap($this->path_cache);

			$this->browser = $browser->getBrowser();

			unset($browser);
		}
		//Funções gerenciamento de links de css
		public function add_css_linked($link, $media="all", $is_local=true){

			$this->css_linked[$link] =  array("link"=>$link, "media"=>$media, "is_local"=>$is_local);
		}
		public function drop_css_linked($link){
			if(isset($this->page->css_linked[$link])){
				unset($this->page->css_linked[$link]);
				
			} else {
				$this->warnings[] = "CSS that you tried to drop haven't been added";
				$this->print_errors();

			}
		}
		public function add_css_inline($css=array()){
			if(is_array($css)){
				foreach($css as $obj => $style){
					if(!isset($this->css_inline[$obj])){
						$this->css_inline[$obj] = $style; 	
					} else {
						$this->css_inline[$obj] = $this->css_inline[$obj]." ".$style; 
					}
					
				}
			} else {
				$this->errors[] = "function add_css_inline expects parameter \$css to be an array";
				$this->print_errors();
			}
		}
		//Funções gerenciamento de links js
		public function add_js_linked($link, $is_local=true, $js_top = false){
			$this->js_linked[$link] =  array("link"=>$link, "is_local"=>$is_local, "depends"=>true, "js_top"=>$js_top);
		}
		
		public function drop_js_linked($link){
			if(isset($this->js_linked[$link])){
				unset($this->js_linked);
			} else {
				$this->warnings[] = "JS that you tried to drop haven't been added";
				$this->print_errors();

			}
		}
		public function add_js_inline($name,$script){
			$this->js_inline[$name] = $script;
		}

		//funções para gerenciamento de metainformações
		public function set_body_class($class){
			$this->body_class = $class;
		}

		public function set_site_container_class($class){
			$this->site_container_class = $class;
		}
		public function set_title($title){
			$this->title = $title;
		}
		public function add_meta($name, $content){
			$this->metas[$name] = $content;
		}
		public function drop_meta($name){
			if(isset($this->metas[$name])){
				unset($this->metas[$name]);
				return true;
			} else {
				return false;
			}
		}
		public function set_html_lang($lang){
			$this->html_lang = $lang;
		}
		//Funções retorno de css e js
		public function get_css_links(){
			if(count($this->css_linked) > 0){
			/*global $path_css;
			$css_links = array();
			if(!is_array($this->css_common)){
				$this->css_common = array();
			}
			$css_all = array_merge($this->css_common, $this->css_linked);
			
			foreach($css_all as $link => $css){
				$path = ($css["is_local"] = true) ? $path_css."/".$link : $link;

					$css_link = "<link rel='stylesheet' type='text/css' href='".$path."' media='".$css['media']."' />";				
					$css_links[$link] = $css_link;
				
				
			}
			return $css_links; */
			
			$timeMod = 0;
			$css_links = array();
			$css_all = $this->css_linked;
			$name = "";
			$content = "";
			foreach($css_all as $link => $css){
				if($css['is_local']){
					$file = $this->path_css."/".$link;
					$mod = filectime($file);
					$timeMod = ($timeMod < $mod) ? $mod : $timeMod;
					$name .= "-".str_replace(".css","",$link);
				}
				else {
					$file = $link;
					$name .= "-".str_replace("/","-",$link);
				}		
			}
			$name .= "-".str_replace("/","-",$_SERVER['HTTP_USER_AGENT']);
			$name = $name.".css";
			
			$arquivo = $this->path_cache."/$name";
			$modFile = 0;
			if(file_exists($arquivo)){
				$modFile = filectime($arquivo);
			}
			if(($modFile == 0 or $modFile < $timeMod) and count($css_all) > 0){

				require_once("lessc.php");
				$less = new lessc;
				$cssFinal = fopen($arquivo,"w+");
				$browser = $this->browser->Browser;
				$version = $this->browser->Version;
				$MajorVer = $this->browser->MajorVer;
				$MinorVer = $this->browser->MinorVer;

				foreach($css_all as $link => $css){
					if($css['is_local']){
						$file = $this->path_css."/".$link;
					}
					else {
						$file = $link;
					}
					$css = file($file);
					foreach($css as $l=>$rule){
						$css[$l] = str_replace("css_path",$this->base_css,$rule);
						if(preg_match("/^[\s]*if(.+):$/",$rule,$match)){
							$css[$l] = str_replace($match[0], "<?php if(".$match[1].") { ?>",$rule);	
						}
						if(preg_match("/^[ \t\s]*end[ \t\s]*$/",$rule,$match)){
							$css[$l] = str_replace("end", "<?php } ?>",$css[$l]);
						}
						if(preg_match("/else:/",$rule,$match)){
							$css[$l] = str_replace("else:", "<?php } else { ?>",$css[$l]);
						}
						if(preg_match("/elseif  *(.+) *:/",$rule,$match)){
							$css[$l] = str_replace($match[0], "<?php } elseif(".$match[1].") { ?>",$rule);
						}

					}
					file_put_contents(str_replace($this->path_css,$this->path_cache,$file), $css);
					$file = str_replace($this->path_css,$this->path_cache,$file);
					ob_start();
					include $file;
			        $min = ob_get_clean();

					
					
/*					$min = file_get_contents($file);
*/					$content .= $min."\n\n";
				}
				unset($browser);
				unset($version);
				unset($MajorVer);
				unset($MinorVer);

				$content = $less->compile($content);
				$content = str_replace("\n","",$content);
				fwrite($cssFinal, $content);
				fclose($cssFinal);
			}
			return array("all"=>"<link rel='stylesheet' type='text/css' href='".$this->base_cache."/$name' media='all' />");
			} else {
				return array("all"=>"");

			}
		}
		public function get_css_inline(){
			if(count($this->css_inline) > 0){
			$css_inline = "<style>";
			foreach($this->css_inline as $obj => $style){
				$css_inline .= "
$obj { $style }";
			}
			$css_inline .= "
</style>";
			return $css_inline;
			} else {
				return false;
			}
		}

		public function get_js_links($pos=false){
			global $path_js;
			$js_links = array();
			$js_all = $this->js_linked;
			if($pos){
				foreach($js_all as $link=>$js){
					$flagTop = ($pos == "top") ? true : false;
					if($js['js_top'] != $flagTop){
						unset($js_all[$link]);
					}
				}
			}
			foreach($js_all as $link => $js){
				$path = ($js["is_local"]) ? $path_js."/".$link : $link;
				$js_link = "<script src=\"$path\"></script>";				
				$js_links[$link] = $js_link;
			}
			return $js_links;
		}
		public function get_js_inline(){
			$html = '
<script name="js-inline">
			$(document).ready(function(){
			';
			foreach($this->js_inline as $name=>$script){
				$html .= <<<JSINLINE
					$script
				
JSINLINE;
			}
			$html .="
		});
</script>";
			return $html;
		}
		//Funções gerenciamento de erros;
		public function print_errors(){
			foreach($this->warnings as $warning){
				echo "<strong>Warning:</strong> $warning <br />";
			}
			if(count($this->errors) > 0){
				foreach($this->errors as $error){
				echo "<strong>Fatal Error:</strong> $error <br />";
				}
				
				die();
			}
		}
		public function add_warning($warning="", $line=__LINE__, $file=__FILE__){
			$this->warnings[] = $warning." on file $file at line $line";
		}
		//Funções para retorno de código
		public function get_head(){
			$head_html = "<head>";
			foreach($this->metas as $meta){
				$head_html .= "
<meta $meta />";
			}
			$css_links = implode("\n", $this->get_css_links());
			$js_links = implode("\n", $this->get_js_links("top"));
			$css_inline = $this->get_css_inline();
			$head_html .= "
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head>
<title>".$this->title."</title>
$css_links
$js_links
$css_inline
</head>";
		return $head_html;
		}
		
		protected function get_content() {
			return $this->content;
		}
		public function set_content($content) {
			$this->content = $content;
		}
		public function get_body(){
			$id = $this->body_id;
			$class = $this->body_class;
			$body = $this->get_content();
			$js_links = implode("\n", $this->get_js_links("bottom"));
			$js_inline = $this->get_js_inline();
			$site_container = $this->site_container;
			$site_container_class = $this->site_container_class;
			$html_body = <<<EOD
<body id="$id" class='$class'>
	<div id="$this->site_container" class='$site_container_class'>
	$body

	</div>
	$js_links
	$js_inline
</body>
EOD;
		return $html_body;
	    }
	    
	    public function create(){
	    	$this->print_errors();
	    	$body = $this->get_body();
	    	$html = $this->doctype."\n<html lang=\"{$this->html_lang}\">\n".$this->get_head()."\n".$body."\n</html>";
	    	return $html;

	    }
	    //Funções extras
	    public function get_requires(){
   			global $path_root, $path_base, $path_models, $path_js, $path_css, $path_common, $path_controllers,$path_datamgr;

			foreach($this->requires as $require){
				require_once($require);
			}
		}
		public function get_data(){
	
	    }
	}	
?>
