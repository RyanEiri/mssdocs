<?php
/*****************************************************
*  
*  Advanced Array Sort
*  (C)opyright 2005 by Matthias Rothe
*  
*  Last modified: 2005-05-20
*  
*  This PHP-Script is released under GNU GPL Licence
*  (see license.txt for more information)
*  
*  @version 0.1
*  @author Matthias Rothe <matthias.rothe@gmx.com>
*  @copyright GNU General Public Licence
*  
*  Please see readme.txt for general help.
*  
*****************************************************/

class array_sort {
  var $sorted_array;
  var $sort_by;
  var $sort_function;
  var $error;
  
  function array_sort($arr, $sort_string, $sort_function = "strcasecmp"){
	$this->error["flag"] = FALSE;
	$this->sorted_array = $arr;
	$this->sort_function = $sort_function;
	$this->sort_by = $this->parse_sort_string($sort_string);
	if(is_numeric($this->sort_by)){
	  $this->error["flag"] = TRUE;
	  settype($this->sort_by, "integer");
	  switch($this->sort_by){
		case -1:
		  $this->error["msg"] = "Syntactical Error in Sort String";
		  break;
		case -2:
		  $this->error["msg"] = "Semantical Error in Sort String";
		}
	  }
	}

  function parse_sort_string($sort_string){
	$sort_by = explode("|", $sort_string);
	foreach($sort_by as $i => $sort_by_part){
	  $syntax_check = $this->check_syntax($sort_by_part);
	  if(!$syntax_check) return -1;
	  else $sort_by[$i] = $syntax_check;
	  }
	unset($sort_by_part);
	unset($syntax_check);
	foreach($sort_by as $i => $sort_by_part){
	  if(eregi("asc", $sort_by[$i][1])) $sort_by[$i][1] = "ASC";
	  else if(eregi("desc", $sort_by[$i][1])) $sort_by[$i][1] = "DESC";
	  }
	unset($sort_by_part);
	$sort_count = count($sort_by);
	if($sort_count > 1){
	  for($i = 1; $i < $sort_count; $i++){
		if(count($sort_by[0][0]) != count($sort_by[$i][0])) return -2;
		}
	  $index_count = count($sort_by[0][0]);
	  for($i = 0; $i < $sort_count - 1; $i++){
		for($j = $i + 1; $j < $sort_count; $j++){
		  $dupl_e_flag = 0;
		  for($k = 0; $k < $index_count; $k++){
			if($sort_by[$i][0][$k] == $sort_by[$j][0][$k]) $dupl_e_flag++;
			}
		  if($sort_by[$i][1] == $sort_by[$j][1]) $dupl_e_flag++;
		  if($dupl_e_flag == $index_count + 1) return -2;
		  }
		}
	  }
	foreach($sort_by as $sort_by_part)
	  if(!$this->check_semantic($sort_by_part[0])) return -2;
	return $sort_by;
	}
  
  function check_syntax($string){
	if(!ereg(">.+<", $string)){
	  if(ereg("><", $string)){
		if(eregi("^<\[[0-9]+.*\]><asc>$|^<\[[0-9]+.*\]><desc>$", $string)){
		  $str_exp = explode("><", $string);
		  }
		else return FALSE;
		}
	  elseif(ereg("^<\[[0-9]+.*\]>$", $string)){
		$str_exp[0] = ereg_replace(">", "",$string);
		$str_exp[1] = "asc";
		}
	  else return FALSE;
	  }
	else return FALSE;
	
	if(isset($str_exp)){
	  $str_exp[0] = ereg_replace("<", "", $str_exp[0]);
	  $str_exp[1] = ereg_replace(">", "", $str_exp[1]);
	  $str_exp[0] = explode("][", $str_exp[0]);
	  foreach($str_exp[0] as $i => $index){
		$index = ereg_replace("^\[", "", $index);
		$str_exp[0][$i] = ereg_replace("\]$", "", $index);
		if(!ereg("^[0-9]+$", $str_exp[0][$i])) return FALSE;
		}
	  }
	
	return $str_exp;
	}
  
  function check_semantic($sort_params){
    $maxkey = count($sort_params) - 1;
	foreach($this->sorted_array as $i => $row){
	  $index = "[".$i."]";
	  for($j = 0; $j <= $maxkey; $j++){
		$key = $sort_params[$j];
		$code = "return array_key_exists(".$key.", \$this->sorted_array".$index.");";
		$result = eval($code);
		if(!$result) break;
		$index .= "[".$sort_params[$j]."]";
	    }
	  if(!$result) break;
	  }
	return $result;
	}

  function get_sorted_array(){
	foreach($this->sort_by as $i => $sort_parameters){
	  if(!isset($max_param)) $max_param = 0;
	  $sort_func[$i][0] = $sort_parameters[0];
	  $sort_func[$i][1] = "compare_by_";
	  $sort_func[$i][1] .= implode("_", $sort_parameters[0]);
	  if($sort_parameters[1] == "DESC") $sort_func[$i][1] .= "_rvs";
	  if(count($sort_parameters[0]) - 1 > $max_param) $max_param = count($sort_parameters[0]) - 1;
	  }
	$eval_result = $this->create_sort_funcs($sort_func, $max_param);
	if($eval_result == NULL){
	  return do_sort($this->sorted_array, $sort_func);
	  }
	else return $this->sorted_array;
	}
  
  function create_sort_funcs($sort_func, $max_param){
	$parameter = "i";
	$file_content = "function do_sort(\$sorted_array, \$sort_func){\n";
	$file_content .= "  usort(\$sorted_array, \$sort_func[0][1]);\n";
	if(count($sort_func) > 1){
	  $file_content .= "  \$offset = -1;\n";
	  $file_content .= "  \$row_count = count(\$sorted_array) - 1;\n";
	  $file_content .= "  for(\$i = 0; \$i <= \$row_count; \$i++){\n";
	  $file_content .= "	if(\$i == 0){\n";
	  $this->line_nextline_equal($file_content, $sort_func);
	  $file_content .= "	  }\n";
	  $file_content .= "	elseif(\$i > 0 && \$i < \$row_count){\n";
	  $this->line_nextline_equal($file_content, $sort_func);
	  $file_content .= "	  else\n";
	  $this->line_previousline_equal($file_content, $sort_func);
	  $file_content .= "	elseif(\$i == \$row_count){\n";
	  $this->line_previousline_equal($file_content, $sort_func);
	  $file_content .= "	}\n";
	  }
	$file_content .= "  return \$sorted_array;\n";
	$file_content .= "  }\n\n";
	for($i = 0; $i <= $max_param; $i++){
	  $file_content .= "function compare_by_";
	  for($j = 0; $j <= $i; $j++){
		$file_content .= chr(ord($parameter) + $j);
		if($j < $i) $file_content .= "_";
		}
	  $file_content .= "(\$a, \$b,";
	  for($j = 0; $j <= $i; $j++){
		$file_content .= " \$";
		$file_content .= chr(ord($parameter) + $j);
		if($j < $i) $file_content .= ",";
		}
	  $file_content .= "){\n";
	  $file_content .= "  return ".$this->sort_function."(\$a";
	  for($j = 0; $j <= $i; $j++){
		$file_content .= "[\$";
		$file_content .= chr(ord($parameter) + $j);
		$file_content .= "]";
		}
	  $file_content .= ", \$b";
	  for($j = 0; $j <= $i; $j++){
		$file_content .= "[\$";
		$file_content .= chr(ord($parameter) + $j);
		$file_content .= "]";
		}
	  $file_content .= ");\n  }\n\n";
	  }
	$funcs = count($sort_func) - 1;
	for($i = 0; $i <= $funcs; $i++){
	  $file_content .= "function ".$sort_func[$i][1]."(\$a, \$b){\n";
	  $file_content .= "  return compare_by_";
	  $param_count = count($sort_func[$i][0]) - 1;
	  for($j = 0; $j <= $param_count; $j++){
		$file_content .= chr(ord($parameter) + $j);
		if($j < $param_count) $file_content .= "_";
		}
	  if(ereg("_rvs$", $sort_func[$i][1])) $file_content .= "(\$b, \$a, ";
	  else $file_content .= "(\$a, \$b, ";
	  $params = str_replace("compare_by_", "", $sort_func[$i][1]);
	  $params = str_replace("_rvs", "", $params);
	  $params = str_replace("_", ", ", $params);
	  $file_content .= $params;
	  $file_content .= ");\n  }\n\n";
	  }
	return eval($file_content);
	}
  
  function line_nextline_equal(&$file_content, $sort_func){
	$file_content .= "	  if(\$sorted_array[\$i]";
	foreach($sort_func[0][0] as $i => $func_param){
	  $file_content .= "[\$sort_func[0][0][".$i."]]";
	  }
	$file_content .= " == \$sorted_array[\$i + 1]";
	foreach($sort_func[0][0] as $i => $func_param){
	  $file_content .= "[\$sort_func[0][0][".$i."]]";
	  }
	$file_content .= "){\n";
	$file_content .= "		if(\$offset == -1) \$offset = \$i;\n";
	$file_content .= "		\$sub_array[\$i] = \$sorted_array[\$i];\n";
	$file_content .= "		}\n";
	}
  
  function line_previousline_equal(&$file_content, $sort_func){
	$file_content .= "	  if(\$sorted_array[\$i]";
	foreach($sort_func[0][0] as $i => $func_param){
	  $file_content .= "[\$sort_func[0][0][".$i."]]";
	  }
	$file_content .= " == \$sorted_array[\$i - 1]";
	foreach($sort_func[0][0] as $i => $func_param){
	  $file_content .= "[\$sort_func[0][0][".$i."]]";
	  }
	$file_content .= "){\n";
	$file_content .= "		\$sub_array[\$i] = \$sorted_array[\$i];\n";
	$file_content .= "		if(count(\$sort_func) > 2){\n";
	$file_content .= "		  \$sort_func_count = count(\$sort_func) - 1;\n";
	$file_content .= "		  for(\$j = 1; \$j <= \$sort_func_count; \$j++){\n";
	$file_content .= "			\$sub_sort_func[\$j - 1] = \$sort_func[\$j];\n";
	$file_content .= "			}\n";
	$file_content .= "		  \$sub_array = do_sort(\$sub_array, \$sub_sort_func);\n";
	$file_content .= "		  }\n";
	$file_content .= "		else usort(\$sub_array, \$sort_func[1][1]);\n";
	$file_content .= "		foreach(\$sub_array as \$j => \$sub_row){\n";
	$file_content .= "		  \$sorted_array[\$offset + \$j] = \$sub_row;\n";
	$file_content .= "		  }\n";
	$file_content .= "		\$offset = -1;\n";
	$file_content .= "		unset(\$sub_array);\n";
	$file_content .= "		unset(\$j);\n";
	$file_content .= "		unset(\$sub_row);\n";
	$file_content .= "		unset(\$sub_sort_func);\n";
	$file_content .= "		unset(\$sort_func_count);\n";
	$file_content .= "	    }\n";
	$file_content .= "	  }\n";
	}
  }
?>
