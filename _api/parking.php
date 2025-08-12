<?php

ob_start(); 

require('pdf/fpdf.php');
require('pdf/converter/image_converter.php');
Header('Pragma: public');
require_once ("../cn/access.php");

// It will be called downloaded.pdf

//print watermark
class PDF_Rotate extends FPDF
{
var $angle=0;
function Rotate($angle,$x=-1,$y=-1)
 {
	if($x==-1)
		$x=$this->x;
	if($y==-1)
		$y=$this->y;
	if($this->angle!=0)
		$this->_out('Q');
	$this->angle=$angle;
	if($angle!=0)
	{
		$angle*=M_PI/180;
		$c=cos($angle);
		$s=sin($angle);
		$cx=$x*$this->k;
		$cy=($this->h-$y)*$this->k;
		$this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',$c,$s,-$s,$c,$cx,$cy,-$cx,-$cy));
	}
}
function _endpage()
{
	if($this->angle!=0)
	{
		$this->angle=0;
		$this->_out('Q');
	}
	parent::_endpage();
}

}
//inherits watermark to pdf
class PDF extends PDF_Rotate{
//barcode generation
function Code39($xpos, $ypos, $code, $baseline=0.5, $height=5){
	$wide = $baseline;
	$narrow = $baseline / 3 ; 
	$gap = $narrow;
	$barChar['0'] = 'nnnwwnwnn';
	$barChar['1'] = 'wnnwnnnnw';
	$barChar['2'] = 'nnwwnnnnw';
	$barChar['3'] = 'wnwwnnnnn';
	$barChar['4'] = 'nnnwwnnnw';
	$barChar['5'] = 'wnnwwnnnn';
	$barChar['6'] = 'nnwwwnnnn';
	$barChar['7'] = 'nnnwnnwnw';
	$barChar['8'] = 'wnnwnnwnn';
	$barChar['9'] = 'nnwwnnwnn';
	$barChar['A'] = 'wnnnnwnnw';
	$barChar['B'] = 'nnwnnwnnw';
	$barChar['C'] = 'wnwnnwnnn';
	$barChar['D'] = 'nnnnwwnnw';
	$barChar['E'] = 'wnnnwwnnn';
	$barChar['F'] = 'nnwnwwnnn';
	$barChar['G'] = 'nnnnnwwnw';
	$barChar['H'] = 'wnnnnwwnn';
	$barChar['I'] = 'nnwnnwwnn';
	$barChar['J'] = 'nnnnwwwnn';
	$barChar['K'] = 'wnnnnnnww';
	$barChar['L'] = 'nnwnnnnww';
	$barChar['M'] = 'wnwnnnnwn';
	$barChar['N'] = 'nnnnwnnww';
	$barChar['O'] = 'wnnnwnnwn'; 
	$barChar['P'] = 'nnwnwnnwn';
	$barChar['Q'] = 'nnnnnnwww';
	$barChar['R'] = 'wnnnnnwwn';
	$barChar['S'] = 'nnwnnnwwn';
	$barChar['T'] = 'nnnnwnwwn';
	$barChar['U'] = 'wwnnnnnnw';
	$barChar['V'] = 'nwwnnnnnw';
	$barChar['W'] = 'wwwnnnnnn';
	$barChar['X'] = 'nwnnwnnnw';
	$barChar['Y'] = 'wwnnwnnnn';
	$barChar['Z'] = 'nwwnwnnnn';
	$barChar['-'] = 'nwnnnnwnw';
	$barChar['.'] = 'wwnnnnwnn';
	$barChar[' '] = 'nwwnnnwnn';
	$barChar['*'] = 'nwnnwnwnn';
	$barChar['$'] = 'nwnwnwnnn';
	$barChar['/'] = 'nwnwnnnwn';
	$barChar['+'] = 'nwnnnwnwn';
	$barChar['%'] = 'nnnwnwnwn';
	$this->SetFont('Arial','',10);
	$this->Text($xpos, $ypos + $height + 4, $code);
	$this->SetFillColor(0);
	$code = '*'.strtoupper($code).'*';
	for($i=0; $i<strlen($code); $i++){
		$char = $code[$i];
		if(!isset($barChar[$char])){
			$this->Error('Invalid character in barcode: '.$char);
		}
		$seq = $barChar[$char];
		for($bar=0; $bar<9; $bar++){
			if($seq[$bar] == 'n'){
				$lineWidth = $narrow;
			}else{
				$lineWidth = $wide;
			}
			if($bar % 2 == 0){
				$this->Rect($xpos, $ypos, $lineWidth, $height, 'F');
			}
			$xpos += $lineWidth;
		}
		$xpos += $gap;
	}
}
//Page header
function Header(){
    $this->Image('../img/logo/sp_logo.png',20,10,50);
    $this->SetFont('Arial','B',15);
    
    $this->Ln(2);
    $this->SetFont('Arial','BI',8);
    $this->SetTextColor(0,0,0);
    $this->SetFont('Arial','BI',7);
    $this->SetX(55);
    $this->SetX(70);
    $this->SetFont('Arial','',13);
    $this->SetTextColor(55,24,60);
    $this->Cell(120,5,'WINGO Spare part',0,0,'R');
    $this->Ln(6);
    $this->SetX(70);
    $this->Cell(120,5,'',0,0,'R');
    $this->Ln(6);
    $this->SetTextColor(0,0,0);
    $this->SetFont('Arial','',8);
    $this->SetX(70);
    $this->Cell(120,5,'VAT NO: 111122133',0,0,'R');
    $this->Ln(5);
    /************************************************/
    $this->SetTextColor(0,0,0);
    $this->SetFont('Arial','',8);
    $this->SetX(70);
    $this->Cell(120,5,'Tel: +250 788 354 149',0,0,'R');
    $this->Ln(5);
    $this->SetX(70);
    $this->Ln(5);
    $this->SetTextColor(0,0,0);
    $this->SetFont('Arial','',8);
    $this->SetX(70);
    $this->Cell(120,5,'Email:@gmail.com',0,0,'R');
    $this->Ln(5);
    $this->SetX(70);
    $this->Cell(120,5,'',0,0,'R');
    $this->Ln(5);
    $this->SetTextColor(53,75,136);
    $style = array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 1, 'color' => array(53,75,136));
    $this->Line(0, 50, 300, 50,$style);	 
    $this->SetTextColor(0,0,0);
    $this->SetFont('Arial','BU',12);
}
function Footer(){
    $this->SetY(-35);
    
    //Position at 1.5 cm from bottom
    $this->SetTextColor(0,0,0);
    $this->SetFont('Arial','B',10);
    
    
    
    $this->SetFont('Arial','',9);	
    $this->Cell(57,5,$_REQUEST['names'],0,1,'C');
    $this->SetFont('Arial','B',11);	
    $this->Cell(57,5,'Store Manager',0,1,'C');
    $this->SetFont('Arial','',7);	
    $this->Cell(72,10,'Issued at '.$_REQUEST['siteName']." On ".date("d-M-Y",time()),0,1,'C');
    
    $this->SetX(70);
    
    //Position at 1.5 cm from bottom
    $this->SetTextColor(0,0,0);
    $this->SetFont('Arial','B',10);	
    
    // $this->SetY(-40);
    // $this->SetTextColor(0,0,0);
    // $this->SetFont('Arial','I',8);	 
    // $this->Cell(0,38,"Bank account: 005-01390161060-56 (COGEBANQUE)",0,1,'R');
    
    $this->SetY(-38);
    $this->Cell(0,40,'_______________________________________________________________________________________________________',0,0,'C');
    $this->Ln(5);
    $this->SetFont('Arial','',10);
    
    $this->SetY(-25);
    $this->SetTextColor(186,53,55);
    $this->SetFont('Arial','BI',8);
    //Position at 1.5 cm from bottom
    $this->Cell(0,30,"Powered BY ITEC Ltd",0,0,'C');
    $this->Ln(4);
    $this->SetTextColor(0,0,0);
    $this->SetFont('Arial','I',8);
    $this->Cell(0,30,"+250 788 644 687",0,0,'C');
    $this->Ln(4);
}

var $B;
var $I;
var $U;
var $HREF;
 function __construct($orientation='P', $unit='mm', $size='A4')
    {
        parent::__construct($orientation, $unit, $size);
        // Initialization
        $this->B=0;
        $this->I=0;
        $this->U=0;
        $this->HREF='';
    }
function WriteHTML($html)
{
    //HTML parser
    $html=str_replace("\n",' ',$html);
    $a=preg_split('/<(.*)>/U',$html,-1,PREG_SPLIT_DELIM_CAPTURE);
    foreach($a as $i=>$e)
    {
        if($i%2==0)
        {
            //Text
            if($this->HREF)
                $this->PutLink($this->HREF,$e);
            else
                $this->Write(5,$e);
        }
        else
        {
            //Tag
            if($e[0]=='/')
                $this->CloseTag(strtoupper(substr($e,1)));
            else
            {
                //Extract attributes
                $a2=explode(' ',$e);
                $tag=strtoupper(array_shift($a2));
                $attr=array();
                foreach($a2 as $v)
                {
                    if(preg_match('/([^=]*)=["\']?([^"\']*)/',$v,$a3))
                        $attr[strtoupper($a3[1])]=$a3[2];
                }
                $this->OpenTag($tag,$attr);
            }
        }
    }
}
function OpenTag($tag,$attr)
{
    //Opening tag
    if($tag=='B' || $tag=='I' || $tag=='U')
        $this->SetStyle($tag,true);
    if($tag=='A')
        $this->HREF=$attr['HREF'];
    if($tag=='BR')
        $this->Ln(5);
}
function CloseTag($tag)
{
    //Closing tag
    if($tag=='B' || $tag=='I' || $tag=='U')
        $this->SetStyle($tag,false);
    if($tag=='A')
        $this->HREF='';
}
function SetStyle($tag,$enable)
{
    //Modify style and select corresponding font
    $this->$tag+=($enable ? 1 : -1);
    $style='';
    foreach(array('B','I','U') as $s)
    {
        if($this->$s>0)
            $style.=$s;
    }
    $this->SetFont('',$style);
}
function PutLink($URL,$txt)
{
    //Put a hyperlink
    $this->SetTextColor(0,0,255);
    $this->SetStyle('U',true);
    $this->Write(5,$txt,$URL);
    $this->SetStyle('U',false);
    $this->SetTextColor(0);
}

}
//Instanciation of inherited class


$pdf=new PDF();

$pdf->SetAuthor('ITEC Tab');
$pdf->SetTitle($_REQUEST['siteName'].' REPORT');


//database code
 

//PDF page content
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Times','',12);
$pdf->SetFont('Arial','B',8);

$ste=$_REQUEST['ste'];
if(!empty($_REQUEST['date_to'])){
    $date21=$_REQUEST['date_to'];  
}
else{
    $date21=date("Y-m-d"); 
}


$sitename = $db->prepare("SELECT site_name FROM `tbl_sites` WHERE  site_id='".$ste."'");
$sitename->execute();
$countsite = $sitename->rowCount();
if($countsite>0){
    $stname = $sitename->fetch();
}

$sthitem = $db->prepare("SELECT *  from tbl_items WHERE item_id='".$item_id300."'");
$sthitem->execute();
$rrritem = $sthitem->fetch(); 

$comp = $_REQUEST['compny'];
     
if($currency_id==2){
    $mbz="usd";
}
else{
    $mbz="Rwf";
}

$pdf->SetFont('Arial','BU',10);   
$pdf->Cell(200,20,'Sales Report ',0,0,'C');
$pdf->Ln();

$pdf->SetFont('Arial','B',8);
$originalDate = $_REQUEST['date_to']; $newDate = date("d F Y", strtotime($originalDate));
$pdf->Cell(150,6,'');
$pdf->Cell(10,6,'Date:',0);
$pdf->Cell(50,6, $newDate,0);
$pdf->Ln();
    
$stmt = $db->prepare("SELECT DISTINCT(t.sell_type) AS st_id, st.sell_type_name FROM tbl_transaction t INNER JOIN tbl_sell_type st ON t.sell_type = st.st_id WHERE t.trans_co = ? and t.pay_mode IS NOT NULL AND DATE_FORMAT(t.trans_date,'%Y-%m-%d') = ? AND t.site_id = ? AND t.status=1");
$stmt->execute([$co_id, $date21, $ste]);

while($sale_type = $stmt->fetch()){
    $pdf->Cell(3,6,'');
    $pdf->Cell(184,6,$sale_type['sell_type_name'],0);
    $pdf->Ln();
    $pdf->Cell(3,6,'');
    $pdf->Cell(11,6,'# ',1);
    $pdf->Cell(60,6,'Item & description',1);
    $pdf->Cell(54,6,'Client(s)',1);
    $pdf->Cell(9,6,'Qty',1,0); 
    $pdf->Cell(21,6,'Sales U.P',1,0);
    $pdf->Cell(26,6,'Sales T.P',1,0);
    $pdf->Ln();
    
    if(empty($Total)){  $amount =0;}else{ $amount = number_format($Total); };
    if(empty($Total2)){  $amount2 =0;}else{ $amount2 = number_format($Total2); };
    if($Homeleaderinv ==0){
        $remain = $Total - $Total2 + $contotal;
    }else{
        $remain = $Total - $Total2;
    }
    
    $tot=0;
    $remain77=0;
    $Total300=0;$Total400=0;
    
    $res = $db->prepare('SELECT * FROM tbl_transaction WHERE trans_co="'.$co_id.'" and pay_mode IS NOT NULL AND DATE_FORMAT(trans_date,"%Y-%m-%d") = "'.$date21.'" AND site_id="'.$ste.'" AND status=1 AND sell_type="'.$sale_type['st_id'].'"  ');
    $res->execute();
    
    $count = $res->rowCount();
    $id=0;
    $sthitem = $db->prepare("SELECT *  from tbl_items WHERE item_id='".$item_id300."'");
    $sthitem->execute();
    $rrritem = $sthitem->fetch();
    
    if($count>0){
        $no=0;
        $Tqty=0;
        $Tcash=0;
        $Tcheque=0;
        $Tmomo=0;
        $Tloan=0;
        $TAll=0;
        $TloanM=0;
        $Tsales=0;
        $Tpur=0;
        
        while($fh = $res->fetch()){
            $no++;
            $item_id = $fh ['item_id']; 
            $item_assgn_id= $fh ['item_assgn_id']; 
             $trans_id=$fh ['trans_id']; 
            $i++;
        
            $uuu100 = $db->prepare("SELECT *FROM tbl_item_assign
            INNER JOIN tbl_items ON tbl_items.item_id = tbl_item_assign.item 
            INNER JOIN tbl_item_stock ON tbl_item_stock.iassign_id =tbl_item_assign.assign_id
            INNER JOIN tbl_ucateg ON tbl_ucateg.ucateg_id = tbl_item_assign.catg_id
            
            INNER JOIN tbl_categories ON tbl_categories.catg_id = tbl_ucateg.categ_id
            INNER JOIN  tbl_units ON  tbl_units.unit_id = tbl_ucateg.unt_id
            
            WHERE tbl_item_assign.assign_id = '".$item_assgn_id."' AND tbl_item_assign.co_assign='".$co_id."' and 	tbl_item_stock.isite_id='".$ste."' ");
            $uuu100->execute();
            $rcount100=$uuu100->rowCount();
            $rwctg = $uuu100->fetch();
            
            $assign_id = $rwctg['assign_id'];
            if($rwctg['unt_id']==7){
                $unit="";  
            }
            else if($rwctg['unt_id']==4 || $rwctg['unt_id']==8){
                $unit="(".$rwctg['unit_name'].")";
            }
            else{
                if($rwctg['piece_no']>1){
                    $unit=" (".$rwctg['piece_no']."X".$rwctg['unit_name'].")";
                }
                else{
                    $unit=" (".$rwctg['unit_name'].")";
                }
            }
        		     
            $resList = $db->prepare('SELECT SUM(outQty) as allQTY, outPrice, SUM(amount) as moneyIn,customer_id,item_assgn_id  FROM tbl_transaction WHERE trans_id="'.$trans_id.'" and outQty IS NOT NULL and pay_mode IS NOT NULL and	trans_co="'.$co_id.'" AND DATE_FORMAT(trans_date,"%Y-%m-%d") = "'.$date21.'" AND site_id="'.$ste.'" AND sell_type = "'.$sale_type['st_id'].'" AND status=1  ');
            $resList->execute();
            
            $countList = $resList->rowCount();
            
            $sthALLp = $db->prepare('SELECT *  from tbl_transaction 
            WHERE trans_id="'.$trans_id.'" and outQty IS NOT NULL and pay_mode IS NOT NULL and
            trans_co="'.$co_id.'" AND DATE_FORMAT(trans_date,"%Y-%m-%d") = "'.$date21.'" AND site_id="'.$ste.'" AND status=1 AND sell_type = "'.$sale_type['st_id'].'"');
            $sthALLp->execute();
        
            $AllData = $sthALLp->rowCount();
            $d1=0;
            $purc=0;
            $salecc=0;
            
            while($rrr2 = $sthALLp->fetch()){
                $d1++;
                
                $item_assgn_idPo= $rrr2['item_assgn_id'];
                
                $sthPo = $db->prepare("SELECT *  from tbl_item_price WHERE  tbl_item_price.assign_id='".$item_assgn_idPo."' and p_status=1 ");
                $sthPo->execute(); 
                $rrrPo = $sthPo->fetch();
                $purc = $purc+$rrrPo['price']; 
                
                $sth = $db->prepare("SELECT *  from tbl_iSte_price WHERE  tbl_iSte_price.sprice_id='".$rrr2['outPrice']."'");
                $sth->execute(); 
                $rrr = $sth->fetch();
                $salecc =$salecc + $rrr['s_price'];  
                $priceOUT = $rrr['s_price'];
            }
            
            $purchaSEF=$purc/$AllData;
            $saleFINAL=$salecc/$AllData;

            $Tqty1=0;
            $Tcash1=0;
            $Tcheque1=0;
            $Tmomo1=0;
            $Tloan1=0;
            $TAll1=0;
            
            $total200=0;
            $salesInfo = $resList->fetch();
            
            $cash=0;
            $checque=0;
            $momo=0;
            $loan=0;
        
            $sth = $db->prepare("SELECT *  from tbl_iSte_price WHERE  tbl_iSte_price.sprice_id='".$salesInfo['outPrice']."' ");
            $sth->execute();
            $rrr = $sth->fetch();
            $priceP = $rrr['p_price']; 
            $priceS = $rrr['s_price']; 
            
            if($salesInfo['pay_mode']==1){
                $cash=$salesInfo['amount'];
                $Tcash=$Tcash+$cash;
            }
            else if($salesInfo['pay_mode']==2){
                $checque=$salesInfo['amount'];
                $Tcheque=$Tcheque+$checque;
            }
            else if($salesInfo['pay_mode']==3){
                $momo=$salesInfo['amount'];
                $Tmomo=$Tmomo+$momo;
            }
            else if($salesInfo['pay_mode']==4){
                $loan=$salesInfo['amount'];
                $Tloan=$Tloan+$loan;
            }
            else if($salesInfo['pay_mode']==5){
                $cashMoMo=$salesInfo['amount'];
                $TloanM=$TloanM+$cashMoMo;
            }
            
            $total=$loan+$momo+$checque+$cash+$cashMoMo;
            $Tqty=$Tqty+$salesInfo['allQTY'];
            $purchase=$purchaSEF*$salesInfo['allQTY'];;
            $sales=$salesInfo['moneyIn'];
            $profit=$sales-$purchase;
            $TAll=$TAll+$profit;
            $Tsales=$Tsales+$sales;
            $Tpur=$Tpur+$purchase;  
        
            $FF=$rwctg['catg_name'];
            $QTY=$salesInfo['allQTY'];
            $rr=$FF.$unit;
             $sthClient = $db->prepare("SELECT *  from tbl_users WHERE  tbl_users.acc_id ='".$salesInfo['customer_id']."'  ");
                            $sthClient->execute(); 
                            $rrrClient = $sthClient->fetch();
                            $clientname=$rrrClient['l_name']." ".$rrrClient['f_name'];
            $pdf->SetFont('Arial','',6);
            $pdf->Cell(3,4,'');
            $pdf->Cell(11,4,$no,1);
            $pdf->Cell(60,4,$rr,1);
            $pdf->Cell(54,4,$clientname,1);
            $pdf->Cell(9,4,$QTY,1,0); 
            // $pdf->Cell(21,4,number_format($saleFINAL),1,0);
            $pdf->Cell(21,4,number_format($salesInfo['moneyIn']/$QTY),1,0);
            $pdf->Cell(26,4,$salesInfo['moneyIn'],1,0);
            $pdf->Ln();
            $total200=$total200+$total;
            
            if($pdf->getY()>250){
                $pdf->AddPage();
                $pdf->setY(60);
            }
        }
        
    }
    $pdf->SetFont('Arial','B',6);		
    $tax1=$remain77*18/100;
    $tax2=$remain77-$tax1;
    $remain77=$remain77+$tax1;
    $pdf->Cell(3,6,'');
    $pdf->Cell(125,6,'Total ',1);
    $pdf->Cell(30,6,' ',1,0); 
    $pdf->Cell(26,6,''.number_format($Tsales).' ',1,0);
    $pdf->Ln();
    $pdf->Ln();
	$pdf->SetFont('Arial','B',8);
}


$pdf->AddPage();
$pdf->setY(80);
#################### Sales Income ###########################
 //loan
        $res5001 = $db->prepare('SELECT SUM(pyt_amt) AS Tcash FROM tbl_pyt WHERE co_py="'.$co_id.'" and trans_id IS NOT NULL and DATE_FORMAT(pay_date,"%Y-%m-%d") = "'.$date21.'" AND py_site="'.$ste.'" AND st=1');
        $res5001->execute();
        $dataCash1=$res5001->fetch();
        $resCashMOMO1 = $db->prepare('SELECT SUM(amount) AS TmomoCash FROM tbl_transaction WHERE trans_co="'.$co_id.'" 
        and DATE_FORMAT(trans_date,"%Y-%m-%d") = "'.$date21.'" AND site_id="'.$ste.'" AND status=1 and pay_mode=4 ');
        $resCashMOMO1->execute();
        $dataMCashMOMO1=$resCashMOMO1->fetch();
        $tloan1=$dataMCashMOMO1['TmomoCash'];
        // -$dataCash1['Tcash']
        //paid loan
        $loanpaidd=0;
         $resPaidloan = $db->prepare('SELECT * FROM tbl_transaction WHERE trans_co="'.$co_id.'" and trans_code IS NOT NULL  AND site_id="'.$ste.'" AND status=1 and pay_mode=4');
        $resPaidloan->execute();
        while($dataPaidloan=$resPaidloan->fetch()){
            $respaid1 = $db->prepare("SELECT SUM(pyt_amt) AS Tcash FROM tbl_pyt WHERE co_py='$co_id' and trans_id='$dataPaidloan[trans_id]' AND py_site='$ste' AND st=1 and rec_date like '$date21%' ");
            $respaid1->execute();
            $datapaid1=$respaid1->fetch();
           //$loanpaidd+=$datapaid1['Tcash'];
            
        }
        
        
        
        
        
        $res500 = $db->prepare('SELECT SUM(pyt_amt) AS Tcash FROM tbl_pyt WHERE co_py="'.$co_id.'" and trans_id IS NOT NULL and DATE_FORMAT(pay_date,"%Y-%m-%d") = "'.$date21.'" AND py_site="'.$ste.'" AND st=1 and py_mode=1 ');
        $res500->execute();
        $dataCash=$res500->fetch();
        
        $res400 = $db->prepare('SELECT SUM(pyt_amt) AS Tmomo FROM tbl_pyt WHERE co_py="'.$co_id.'" and trans_id IS NOT NULL and DATE_FORMAT(pay_date,"%Y-%m-%d") = "'.$date21.'" AND py_site="'.$ste.'" AND st=1 and py_mode=3 ');
        $res400->execute();
        $dataMoMo=$res400->fetch();
        
        $resCashMOMO = $db->prepare('SELECT SUM(amount) AS TmomoCash FROM tbl_transaction WHERE trans_co="'.$co_id.'" and trans_code IS NOT NULL and DATE_FORMAT(trans_date,"%Y-%m-%d") = "'.$date21.'" AND site_id="'.$ste.'" AND status=1 and pay_mode=5 ');
        $resCashMOMO->execute();
        $dataMCashMOMO=$resCashMOMO->fetch();
        
        $resLoan = $db->prepare('SELECT SUM(amount) AS TLoan3 FROM tbl_transaction WHERE trans_co="'.$co_id.'" and trans_code IS NOT NULL and DATE_FORMAT(trans_date,"%Y-%m-%d") = "'.$date21.'" AND site_id="'.$ste.'" AND status=1 and pay_mode=4 ');
        $resLoan->execute();
        $dataMLoan=$resLoan->fetch();
        
        $rescheque = $db->prepare('SELECT SUM(pyt_amt) AS TLcheque FROM tbl_pyt WHERE co_py="'.$co_id.'" and trans_id IS NOT NULL and DATE_FORMAT(pay_date,"%Y-%m-%d") = "'.$date21.'" AND py_site="'.$ste.'" AND st=1 and py_mode=2 ');
        $rescheque->execute();
        $dataMcheque=$rescheque->fetch();
        
        $resExpense = $db->prepare('SELECT SUM(co_amount) AS t_exp FROM tbl_exp_consume WHERE ex_site="'.$ste.'"  and  DATE_FORMAT(co_date,"%Y-%m-%d") = "'.$date21.'" and   st=1  ');
        $resExpense->execute();
        $dataMExpense=$resExpense->fetch();
        
        $resServices = $db->prepare('SELECT SUM(amount) AS t_services FROM tbl_services_payment WHERE site_id="'.$ste.'"  and  DATE_FORMAT(date_created,"%Y-%m-%d") = "'.$date21.'" ');
        $resServices->execute();
        $dataServices=$resServices->fetch();

$pdf->setY($pdf->getY() -25);
$AccountTotal1=$AccountTotal+$dataCash['Tcash']+$dataMoMo['Tmomo']+$tloan1+$dataMcheque['TLcheque'];
$AccountTotal=$AccountTotal+$dataCash['Tcash']+$dataMoMo['Tmomo']+$tloan1+$dataMcheque['TLcheque']-($Tpur+$dataMExpense['t_exp']);

$pdf->Cell(72,6,'Total Sales Revenue: '.number_format($AccountTotal1),1); 
$pdf->Ln();


    $resMode = $db->prepare('SELECT  distinct(py_mode) as py_mode FROM tbl_pyt WHERE co_py="'.$co_id.'" and trans_id IS NOT NULL and DATE_FORMAT(pay_date,"%Y-%m-%d") = "'.$date21.'" AND py_site="'.$ste.'" AND st=1 ');
             $resMode->execute();
             $total111=0;
             $total222=0;
             $aa=0;
             while($resMode1=$resMode->fetch()){
                 //sum of mode
                 $mode=$resMode1['py_mode'];
                 $resModetotal = $db->prepare('SELECT  sum(pyt_amt) as pyt_amt FROM tbl_pyt WHERE co_py="'.$co_id.'" and trans_id IS NOT NULL and DATE_FORMAT(pay_date,"%Y-%m-%d") = "'.$date21.'" AND py_site="'.$ste.'" AND st=1 and py_mode="'.$mode.'"  ');
                $resModetotal->execute();
                $rowTotal=$resModetotal->fetch();
                //mode
                  $acc = $db->prepare("SELECT * from tbl_accounts where acc_id='$mode'  and acc_co_id='$co_id'");
                $acc->execute();
                $acc=$acc->fetch();
                $total111+=$rowTotal['pyt_amt'];

$pdf->SetFont('Arial','',7);
$pdf->Cell(25,6,$acc['acc_mode'].strtoupper($row_count2['te_names']),1); 
$pdf->Cell(15,6,$rowTotal['pyt_amt'],1,0);
$pdf->Cell(12,6,'-',1,0);
$pdf->Cell(20,6,$rowTotal['pyt_amt'],1,0);
$pdf->Ln();

}


$pdf->Cell(25,6,'Loan '.strtoupper($row_count2['tin_no']),1);
$pdf->Cell(15,6,number_format($tloan1),1,0); 
$pdf->Cell(12,6,'-',1,0);
$pdf->Cell(20,6,number_format($tloan1),1,0); 
$pdf->Ln();

$pdf->Cell(25,6,'Paid Loan '.strtoupper($row_count2['tin_no']),1);
$pdf->Cell(15,6,number_format($loanpaidd),1,0); 
$pdf->Cell(12,6,'-',1,0);
$pdf->Cell(20,6,number_format($loanpaidd),1,0); 
$pdf->Ln();




$pdf->Cell(25,6,"Total Expense(s)",1);
$pdf->Cell(15,6,number_format($dataMExpense['t_exp']).' ',1,0);
$pdf->Cell(12,6,'-',1,0);
$pdf->Cell(20,6,number_format($dataMExpense['t_exp']).' ',1,0);
$pdf->Ln();

$pdf->SetFont('Arial','B',7);
$pdf->Cell(52,6,"Net Cash",1);
$pdf->Cell(20,6,number_format(($loanpaidd+$dataCash['Tcash'] + ($dataMoMo['Tmomo'] - ($dataMoMo['Tmomo']*0.5/100)) -$dataMExpense['t_exp']), 2), 1, 0); 
$pdf->Ln();

// $pdf->Cell(36,6,"Net Profit",1);
// $pdf->Cell(36,6,number_format($AccountTotal).' ',1,0); 
// $pdf->Ln(15);
#################### End Sales Income #######################

#################### Loan Payments #######################
$pdf->setY(55);
$lpayments = $db->prepare("SELECT 
                            assn.*,
                            ctg.catg_name,
                            unt.unit_name,
                            ucateg.piece_no,
                            SUM(pyt.pyt_amt) as paid 
                        FROM tbl_item_assign assn
                            INNER JOIN tbl_items ON tbl_items.item_id = assn.item
                            INNER JOIN tbl_transaction t ON t.item_assgn_id = assn.assign_id
                            INNER JOIN tbl_pyt pyt ON t.trans_id = pyt.trans_id
                            INNER JOIN tbl_ucateg ucateg ON ucateg.ucateg_id = assn.catg_id
                            INNER JOIN  tbl_units unt ON  unt.unit_id = ucateg.unt_id
                            INNER JOIN tbl_categories ctg ON ctg.catg_id = ucateg.categ_id
                        WHERE 
                            t.site_id= ? AND
                            t.pay_mode = 4 AND
                            t.status= 1 AND
                            assn.co_assign = ? AND
                            pyt.pay_date = ?
                        GROUP BY t.trans_id
                    ");
                    
$lpayments->execute([$ste, $co_id, $date21]);
$lpaid = 0;
$pdf->SetFont('Arial','B',8);
if($lpayments->rowCount() >0){
    $pdf->setX(100);
    $pdf->Cell(3,6,'');
    $pdf->Cell(100,6,'Loan Payments',1);
    $pdf->Ln();
    $pdf->setX(100);
    $pdf->Cell(3,6,'');
    $pdf->Cell(11,6,'#',1);
    $pdf->Cell(64,6,'Item & description',1);
    $pdf->Cell(25,6,'Paid Amount',1,0);   
    $pdf->Ln();
    $lpi = 1;
    $pdf->SetFont('Arial','',8);
    while($lp = $lpayments->fetch()){
        $pdf->setX(100);
        if($lp['unt_id']==7){
            $unit="";  
        } else if($lp['unt_id']==4){
            $unit="(".$lp['unit_name'].")";
        } else {
            if($lp['piece_no']>1){
                $unit="(".$lp['piece_no']."X".$lp['unit_name'].")";
            } else{
                $unit="(".$lp['unit_name'].")";
            }
        }
        
        $lpaid += $lp['paid'];
        
        $pdf->Cell(3, 6, '');
        $pdf->Cell(11, 6, $lpi++ ,1);
        $pdf->Cell(64, 6, $lp['catg_name']." ".$unit, 1);
        $pdf->Cell(25, 6, $lp['paid'],1,0); 
        $pdf->Ln();
        
        if($pdf->getY()>250){
            $pdf->AddPage();
            $pdf->setY(60);
        }
    }
    $pdf->setY($pdf->getY());
    $pdf->Ln();
    $pdf->SetFont('Arial','B',8);
    
    $res500 = $db->prepare('SELECT SUM(pyt_amt) AS Tcash FROM tbl_pyt WHERE co_py="'.$co_id.'" and pqty IS NULL and DATE_FORMAT(pay_date,"%Y-%m-%d") = "'.$date21.'" AND py_site="'.$ste.'" AND st=1 and py_mode=1 ');
    $res500->execute();
    $dataCash=$res500->fetch();
    
    $res400 = $db->prepare('SELECT SUM(pyt_amt) AS Tmomo FROM tbl_pyt WHERE co_py="'.$co_id.'" and pqty IS NULL and DATE_FORMAT(pay_date,"%Y-%m-%d") = "'.$date21.'" AND py_site="'.$ste.'" AND st=1 and py_mode=3 ');
    $res400->execute();
    $dataMoMo=$res400->fetch();
    
    $rescheque = $db->prepare('SELECT SUM(pyt_amt) AS TLcheque FROM tbl_pyt WHERE co_py="'.$co_id.'" and pqty IS NULL  AND DATE_FORMAT(pay_date,"%Y-%m-%d") = "'.$date21.'" AND py_site="'.$ste.'" AND st=1 and py_mode=2 ');
    $rescheque->execute();
    $dataMcheque=$rescheque->fetch();
    
    $pdf->Cell(93,6,'');
    $pdf->Cell(100,6,'Total Loan Payments: '.number_format($lpaid),1); 
    $pdf->Ln();
    
    $pdf->SetFont('Arial','B',7);
    $pdf->Cell(93,6,''); 
    $pdf->Cell(64,6,'Account',1); 
    $pdf->Cell(36,6,'Total Amount',1,0);
    $pdf->Ln();
    
    $pdf->SetFont('Arial','',8);
    $pdf->Cell(93,6,''); 
    $pdf->Cell(64,6,'Cash',1); 
    $pdf->Cell(36,6,number_format($dataCash['Tcash']),1,0); 	
    $pdf->Ln();
    
    $pdf->Cell(93,6,'');
    $pdf->Cell(64,6,'MoMo',1);
    $pdf->Cell(36,6,number_format($dataMoMo['Tmomo']),1,0); 
    $pdf->Ln();
    
    $pdf->Cell(93,6,'');
    $pdf->Cell(64,6,'Cheque',1);
    $pdf->Cell(36,6,number_format($dataMcheque['TLcheque']),1,0); 
    $pdf->Ln();
}

#################### End Loan Payments #######################

#################### Loans #######################

$ltrans = $db->prepare("SELECT 
                            assn.*,
                            ctg.catg_name,
                            unt.unit_name,
                            ucateg.piece_no,
                            t.amount,
                            t.outQty,
                            u.l_name,
                            u.f_name
                        FROM tbl_item_assign assn
                            INNER JOIN tbl_items ON tbl_items.item_id = assn.item
                            INNER JOIN tbl_transaction t ON t.item_assgn_id = assn.assign_id
                            INNER JOIN tbl_ucateg ucateg ON ucateg.ucateg_id = assn.catg_id
                            INNER JOIN  tbl_units unt ON  unt.unit_id = ucateg.unt_id
                            INNER JOIN tbl_categories ctg ON ctg.catg_id = ucateg.categ_id
                            LEFT JOIN tbl_users u ON t.customer_id = u.acc_id
                        WHERE 
                            t.site_id= ? AND
                            t.pay_mode = 4 AND
                            t.status= 1 AND
                            assn.co_assign = ? AND
                            t.trans_date = ?
                        GROUP BY t.trans_id
                    ");
$pdf->Ln(60);
$ltrans->execute([$ste, $co_id, $date21]);
$ln2024Total = 0;
$pdf->SetFont('Arial','B',8);
if($ltrans->rowCount() >0){
    $pdf->Cell(3,6,'');
    $pdf->Cell(188,6,'Loans',1);
    $pdf->Ln();
    $pdf->Cell(3,6,'');
    $pdf->Cell(8,6,'#',1);
    $pdf->Cell(100,6,'Item & description',1);
    $pdf->Cell(10,6,'Qty',1);
    $pdf->Cell(50,6,'Client',1);
    $pdf->Cell(20,6,'Amount',1,0); 
    $pdf->Ln();
    $lpi = 1;
    $pdf->SetFont('Arial','',8);
    while($ln2024 = $ltrans->fetch()){
        if($ln2024['unt_id']==7){
            $unit="";  
        } else if($ln2024['unt_id']==4){
            $unit="(".$ln2024['unit_name'].")";
        } else {
            if($ln2024['piece_no']>1){
                $unit="(".$ln2024['piece_no']."X".$ln2024['unit_name'].")";
            } else{
                $unit="(".$ln2024['unit_name'].")";
            }
        }
        
        $ln2024Total += $ln2024['amount'];
        
        $pdf->Cell(3, 10, '');
        $pdf->Cell(8, 10, $lpi++ ,1);
        $pdf->Cell(100, 10, $ln2024['catg_name']." ".$unit, 1, false);
        $pdf->Cell(10, 10, $ln2024['outQty'], 1);
        $pdf->Cell(50, 10, substr($ln2024['f_name']." ".$ln2024['l_name'], 0, 20), 1);
        $pdf->Cell(20, 10, number_format($ln2024['amount']),1,0);
        $pdf->Ln();
        
        if($pdf->getY()>250){
            $pdf->AddPage();
            $pdf->setY(60);
        }
    }
}

#################### End Loans #######################

$name1 = $row_count2['te_names'];
$n1=$_REQUEST['siteName'].'_Report_'.$_REQUEST['date_to'];


$pdf->Output($n1.'.pdf','I');
	 

exit;
ob_end_flush();
?>
