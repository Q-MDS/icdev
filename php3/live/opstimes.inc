<?






function vsetdate($tdate)
{

 return 100*date("m",$tdate)+date("d",$tdate)+10000*date("Y",$tdate);

}

function vgetdate($tmp)
{
// echo "vgd-$tmp ";
      $xy=floor($tmp/10000);
      $tmp=$tmp%10000;
      $xm=floor($tmp/100);
      $xd=$tmp%100;
 //   echo "= $xy/$xm/$xd is ";
  //    echo  mktime(0,0,0,$xm,$xd,$xy);
   //   echo "<br>";
      return mktime(0,0,0,$xm,$xd,$xy);


}

function afrikdate($tdate)
{

$afrikd[0]="Son";
$afrikd[1]="Maan";
$afrikd[2]="Dins";
$afrikd[3]="Woens";
$afrikd[4]="Dond";
$afrikd[5]="Vry";
$afrikd[6]="Sat";

$afrikm[1]="Jan";
$afrikm[2]="Feb";
$afrikm[3]="Maart";
$afrikm[4]="Apr";
$afrikm[5]="Mei";
$afrikm[6]="Jun";
$afrikm[7]="Jul";
$afrikm[8]="Aug";
$afrikm[9]="Sep";
$afrikm[10]="Okt";
$afrikm[11]="Nov";
$afrikm[12]="Des";


 
   $mdate=vgetdate($tdate);
   $dow=date("w",$mdate);
   $mon=date("n",$mdate);
//   $mdate=$tdate%100;
//   $adate=$afrikd[$dow]." $mdate ".$afrikm[$mon];
    $adate=date("D d M",$mdate);

   return $adate;
}


function prevdate($bdate,$daysback=1)
{

      $tdate=vgetdate($bdate);
      $tdate=$tdate-(60*60*24*$daysback);
      return vsetdate($tdate);

}

function nextdate($bdate,$daysfwd=1)
{
   // echo "<tr><td colspan=6>Nextdate $bdate<br>";
      $tdate=vgetdate($bdate);
  //     echo "big $tdate ";
      $tdate=$tdate+(60*60*24*$daysfwd);
//      echo "bigger $tdate small again";
 //        echo  vsetdate($tdate);
//      echo "</td></tr>";
      return vsetdate($tdate);

}

?>
