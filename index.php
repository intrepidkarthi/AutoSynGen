<html>
<head>
<title>AutoSynGen - Karthikeyan NG</title>
<script type="text/javascript" src="clienthint.js"></script>

</head>
<body>

<?php

## index.php: Main page for Automatic Synonym Generator Tool 
## Copyright (C) 2009 Madurai, Tamilnadu.
## Author: Karthikeyan NG (intrepidkarthi@gmail.com, www.intrepidkarthi.com)
## This program is free software; you can redistribute it and/or modify
## it under the terms of the GNU General Public License as published by
## the Free Software Foundation; either version 2 of the License, or
## (at your option) any later version.
##
## This program is distributed in the hope that it will be useful, but
## WITHOUT ANY WARRANTY; without even the implied warranty of
## MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
## Library General Public License for more details.
##
## You should have received a copy of the GNU General Public License
## along with this program; if not, write to the Free Software
## Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
## 02111-1307, USA.


//Please go through the read me document before going through code
  
  
   
  include("config.php");//includes Configuration file
  
  //Mysql connection establishment
  $con = mysql_connect($hostname,$username,$password);
  if (!$con)
  {
    die('Could not connect: ' . mysql_error());
  }
  else
  {
   mysql_select_db($dbname);//selecting the mysql database
   
   $val = $_GET["query"];//input keyword from the user
   
   //just for finding the memory usage before allocating any memory
  // echo "before array setup:    ".memory_get_usage()."\n";

   global $temp1,$temp2;
   $rightdata1=array();
      $rightdata2=array();
           $rightdata3=array();
	      $wrongdata=array();
		 $wrongdata1=array();
		    $wrongdata2=array();
                       $wrongdata3=array();
			   $finaloptions=array();	
			      $options=array();	
   //memory size after allocating arrays
  // echo "after array setup:    ".memory_get_usage()."\n";
 
   
   //Query for finding the synonyms of the key word selected by the user
   //There won't be any repitition in the synsets since it is distinct and it is compared with its parent values	
   $dbquery = "select distinct(s2.synset_id) from synset s1,synset s2 where s1.synset_id=s2.synset_id and    s1.word='".$val."' and s2.word!='".$val."' order by s2.synset_id";
   
   //executing the above query
   $result1 = mysql_query($dbquery) or die(mysql_error());
   
   while($row = mysql_fetch_array($result1,MYSQL_BOTH))
   {
     //storing all the children synsets in an array
     $rightdata1[]=$row[0];
       
   }
 // echo "after rightdtaa1:    ".memory_get_usage()."\n";
  

   foreach($rightdata1 as $rd1)
   {
     //Query for finding the synonyms in the second level with Minimal path length == 2 from the root
     //There won't be any repitition in the synsets since it is distinct and it is compared with its parent values 
     $subquery1 = "SELECT DISTINCT(s2.synset_id), s2.word FROM synset s1, synset s2 WHERE s1.word = s2.word AND s1.synset_id =".$rd1." and s2.synset_id!=".$rd1." ORDER BY s2.synset_id";
     //executing the above query 
     $result2 = mysql_query($subquery1) or die(mysql_error());	
      
      while($row = mysql_fetch_array($result2,MYSQL_BOTH))
      {
        //storing the second level of children in an array
        $rightdata2[]=$row[0];
          
      }
     
   }
  // echo "after rightdatat2:    ".memory_get_usage()."\n";
   //After finding the synsets upto two levels, all the synsets are merged into a single array 
   //It is merged because for selecting the three wrong options	
   $rightdata3[] = $val;
   $rightdata3 = array_merge($rightdata1,$rightdata2);
   
  // sort($rightdata3);
  // array_unique($rightdata3); 

   
 //  foreach($rightdata3 as $rd3)
      //   echo "$rd3","\n"; 
    

   //this function selects one correct answer for the question from the first level of synsets with MPL == 1 
   function correctanswer()
    {
      global $rightdata1;
      shuffle($rightdata1);
      $rightanswer = $rightdata1[0];
      return $rightanswer;

    }
   
   //this function selects the distractor from the synsets from the second level with MPL == 2. 
   //It won't be a direct synonym for the question since it is compared with prvious values
   //Otherwise we can have another method for distractor: We can get a synset using "LIKE" clause
   //For example for a keyword "advertise" we can give one of the option as "advert" using Like.
   //But it doesn't work well for all the words. So I am using this. 
   function distractor()
   {
      global $rightdata2;
      shuffle($rightdata2);
      $distractor = $rightdata2[0];
      return $distractor;

   }   
   
   
   //function to get a random synset id  
   function getrandom()
   {
    
     $wronganswer="SELECT distinct(synset_id) FROM `synset` order by rand() limit 1";
      
     $result3 = mysql_query($wronganswer) or die(mysql_error());  

     while($row = mysql_fetch_array($result3,MYSQL_BOTH))
     {
      $wrongdata = $row[0];
     }

    return $wrongdata;
   }
    
    //function for whether the selected value is in the rightdata set
    function valcheck($id)
    { 
      global $rightdata3;
      if(in_array($id,$rightdata3))
        return 0;
      else
	return 1;	  
    } 
  
   //Function for generating three wrong values 
   function wrongvalues()
   {
          global $wrongdata1;
          global $wrongdata2;
          global $wrongdata3;
          

     	  $wrongval = getrandom();
      	 if(valcheck($wrongval))
      	 {
         	 //query to get first level children of the wrong value
        	  $subquery2 =  "SELECT DISTINCT(s2.synset_id), s2.word FROM synset s1, synset s2 WHERE s1.word = s2.word AND s1.synset_id =".$wrongval." and s2.synset_id!=".$wrongval." ORDER BY s2.synset_id";
     
         	 $result4 = mysql_query($subquery2) or die(mysql_error());
    
         	 while($row = mysql_fetch_array($result4,MYSQL_BOTH))
         	 {
            	 $wrongdata1[] = $row[0];//storing the values in an array
         	 }    
	
         	 foreach($wrongdata1 as $a)
          	 {
			//for each wrong value it is checked whether it is already present or not
            		if(valcheck($a)!=1)
               	              wrongvalues(); 
         	 }
          	foreach($wrongdata1 as $a)
          	 { 
/*foreach wrong data its second level synsets are identified. But it is not stored to reduce space complexity. Because in second level there will be a lot of synsets(average:300). So it saves a lot of space and script running time. */                 
            	 	 $subquery3 = "SELECT DISTINCT(s2.synset_id), s2.word FROM synset s1, synset s2 WHERE s1.word = s2.word AND s1.synset_id =".$a." and s2.synset_id!=".$a." ORDER BY s2.synset_id";

	       		 $result5 = mysql_query($subquery3) or die(mysql_error());
    
                 	  while($row = mysql_fetch_array($result5,MYSQL_BOTH))
                   	  {
                           
                  	   $wrongdata2[] = $row[0];
                          }  
  //still optimization can be done here. By avoiding the array $wrongdata2 array        
                	    foreach($wrongdata2 as $b)
                    	    {
                     	      if(valcheck($b))
                              { 
                               continue;
                              }
                              else
                                 wrongvalues();
          
                            }
                  }
           }
           else
             wrongvalues();
    
       

 

   return $wrongval;

  }
  
    
   
    //getting the first wrong option
    $wrongresult[0] = wrongvalues();
   // echo "after wrong1:    ".memory_get_usage()."\n";

    //getting the second wrong option
    while(1)
    {
    $temp = wrongvalues(); 
    if(in_array(temp,$wrongresult))
       continue;
    else
       $wrongresult[1] = $temp;
       break;
    }
   // echo "after wrong2:    ".memory_get_usage()."\n";

    //getting the third wrong option
    while(1)
    {
    $temp = wrongvalues(); 
    if(in_array(temp,$wrongresult))
       continue;
    else{
       $wrongresult[2] = $temp;
       break;}
    }
   // echo "after wrong3:    ".memory_get_usage()."\n";
  
   //three wrong answers generated randomly will be stored inside an array and the older array is unsetted in the next step 
   for($x=0;$x<3;$x++)
   {
      $finaloptions[$x] = $wrongresult[$x];
  
   }
    
        
          
	  
          unset($wrongresult);


          //If there is no correct answer means there wont be any synonym. For example: "xenophobia"  
	  if(correctanswer()==null)
		 $x = 0; //For just bypassing
	  
          //Other the correct answer will be taken from the first level synonym in the right hand side.
          else
                  $finaloptions[3] = correctanswer();


          //If there is no second level child in the right data side then there won't be any distractor.So at that time distractor will be taken from random number generation. 
          if(distractor()==null)
          {
	     while(1)
             {
               $temp = getrandom(); 
               if(in_array(temp,$finaloptions))
                        continue;
               else{
               $finaloptions[4] = $temp;
               break;} 
             }
          }
	  else  
          {
                 
               //if correct answer and final option asre same means then distractor will be taken from random numbers                            
	       if($finaloptions[3]==$finaloptions[4])
               {
                  while(1)
                  {
                     $temp = getrandom(); 
                     if(in_array(temp,$finaloptions))
                                continue;
                     else{
                       $finaloptions[4] = $temp;
                       break;} 
                  }              
               } 

               else
               {
		while(1)
                  {
                     $temp = getrandom(); 
                     if(in_array(temp,$finaloptions))
                                continue;
                     else{
                       $finaloptions[4] = $temp;
                       break;} 
                  }    



               }    	
               
            }
          
		
                
                //shuffle($finaloptions);

                //till now all the dealings with SQL is  done only with the synset_id of each word and not the word
                //now for each synset_id in the final option array, corresponding words are taken
		foreach($finaloptions as $k)
		{
                   $subquery4 = "select word from synset where synset_id=".$k." and word!='".$val."' order by rand() limit 1";
                   $resultlast = mysql_query($subquery4) or die(mysql_error());
    
                   while($row = mysql_fetch_array($resultlast,MYSQL_BOTH))
                   {
                     $options[] = $row[0];
                   }  
                  $secret = $options[3];
                  //the final 5 options for user is shuffled
                  //shuffle($options);   
                            
                }	
                
                echo "<center><h1><font color=blue>Automatic Synonym Generator Tool</font></h1>
<form method=\"get\" action=\"index.php\">
Select Word <input type=\"text\" name=\"query\";id=\"txt1\" onkeyup=\"showHint(this.value)\" />
<input type=\"submit\" value=\"Click\" />

<p>Suggestions: <span id=\"txtHint\"></span></p></center></form> ";
                   
                  if($val!=null)
                  {	 
                   echo "<center>You have selected: <b><font color=green>[".$val."]</font></b>  Now select the synonym for the word</center>";            
	foreach($options as $a)
         	 echo "<center><font size=4>".$a."</font><br></center>";
	echo "<script type=\"text/javascript\">

function getgoing()
  {
    window.location=\"./index.php\";
   }



function callme()
{
  
  var ans = \"$secret\";
  var userans = document.getElementById(\"tbox\").value;
  
  if(ans==userans)
  {
    alert(\"you are right buddy\");
    setTimeout('getgoing()',500);


  } 
  else
  {
    alert(\"better luck nex time\"); 
    setTimeout('getgoing()',500);

  } 

}

</script>";
                   	echo "<br><center>Type Answer Here:<input type=text id =tbox name=tbox/></center></br>"; 
                        echo "<br><center><input type=\"button\" onclick=\"callme()\" value=\"Click for answer\" /></center>";
                  }
                  else
                  { 
                            echo "<center><b><font color=red>Can you please enter a word in the text box?</font></b></center>";
                  } 

                
		      
                unset($rightdata1);
		unset($rightdata2);
		unset($rightdata3);
		unset($wrongdata);
		unset($wrongdata1);
		unset($wrongdata2);
		unset($wrongdata3);
		unset($finaloptions);
		unset($options);
              //  echo "after completing all setup:    ".memory_get_usage()."\n";

         

 
   mysql_close($con);  
  }



echo( "<br><br><br>Date:<b>".date("l, F dS Y.")."</b><br>" );
echo "Developed by:<font color=green><b>Karthikeyan NG</b></font>";
echo "<a href=\"autosyngen.pdf\">Download Documentation Here</a>";

?>






</body>
</html>
