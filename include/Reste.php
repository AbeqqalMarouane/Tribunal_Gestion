<?php
ob_start(); // Start output buffering

// Include the main TCPDF library (search for installation path).
require('../tcpdf-main/tcpdf.php');

// Extend TCPDF with custom functions
class MYPDF extends TCPDF {

    // Load table data from file
    public function LoadData() {
        include('dbConn.php');
    
        $sql = "
        SELECT 
            Nbr_Financier, 
            CIN, 
            Prenom, 
            Nom,  
            Rest_Temp,
            NULL AS Date_Debut, 
            NULL AS vice_nom_prenom,
            NULL AS sortie,
            NULL AS Date_retour,
            NULL AS entre
        FROM 
            users;
        ";
    
        $stmt = $dbh->prepare($sql);
        $stmt->execute();
        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }
    

    // Colored table
    public function ColoredTable($header, $data) {
        // Colors, line width and bold font
        $this->SetFillColor(1, 103, 1);
        $this->SetTextColor(255);
        $this->SetDrawColor(128, 0, 0);
        $this->SetLineWidth(0.3);
        $this->SetFont('arial', 'B', 12); // Set font to Arial Bold
        // Header
        $w = array(25, 25, 25, 25, 25, 30, 25, 25, 30, 25); // Adjusted column widths
        $num_headers = count($header);
        for ($i = 0; $i < $num_headers; ++$i) {
            $this->MultiCell($w[$i], 18, $header[$i], 1, 'C', 1, 0, '', '', true);
        }
        $this->Ln();
        // Color and font restoration
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('arial', '', 12); // Set font to Arial Regular
        // Data
        $fill = 0;
        foreach($data as $row) {
            $this->MultiCell($w[0], 18, $row["Nbr_Financier"], 'LR', 'C', $fill, 0, '', '', true);
            $this->MultiCell($w[1], 18, $row["CIN"], 'LR', 'C', $fill, 0, '', '', true);
            $this->MultiCell($w[2], 18, $row["Prenom"], 'LR', 'C', $fill, 0, '', '', true);
            $this->MultiCell($w[3], 18, $row["Nom"], 'LR', 'C', $fill, 0, '', '', true);
            $this->MultiCell($w[4], 18, $row["Reste_Conge"], 'LR', 'C', $fill, 0, '', '', true);
            $this->MultiCell($w[5], 18, $row["Date_Debut"], 'LR', 'C', $fill, 0, '', '', true);
            $this->MultiCell($w[6], 18, $row["vice_nom_prenom"], 'LR', 'C', $fill, 0, '', '', true);
            $this->MultiCell($w[7], 18, $row["sortie"], 'LR', 'C', $fill, 0, '', '', true);
            $this->MultiCell($w[8], 18, $row["Date_retour"], 'LR', 'C', $fill, 0, '', '', true);
            $this->MultiCell($w[9], 18, $row["entre"], 'LR', 'C', $fill, 0, '', '', true);

            $this->Ln();
            $fill = !$fill;
            if ($this->GetY() + 18 > $this->PageBreakTrigger) {
                $this->AddPage($this->CurOrientation);
            }
        }
        $this->Cell(array_sum($w), 0, '', 'T');
    }

    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

// create new PDF document with A4 landscape orientation
$pdf = new MYPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 011');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true); // Enable footer

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);

}

// Set RTL direction
$pdf->setRTL(true);

// ---------------------------------------------------------

// set font to Arial
$pdf->SetFont('arial', '', 12);

// add a page
$pdf->AddPage();

// Get the current date
$current_date = date('Y');

// Add the "logo" text at the top right corner
$pdf->SetFont('arial', '', 10); // Set font to Arial Regular, size 10
$pdf->SetXY($pdf->getPageWidth() -280, 10); // Positioning the text at the top right corner
$pdf->MultiCell(60, 5, "المملكة المغربية\nوزارة العدل\nمحكمة الاستئناف بفاس\nمحكمة الابتدائية بفاس\nالنيابة العامة\n", 0, 'R', 0, 0, '', '', true);

// Add the title
$pdf->SetFont('arial', 'B', 16); // Set font to Arial Bold, size 16
$pdf->Ln(20); // Add a line break to move the title below the logo text
$pdf->Cell(0, 20, 'قائمة الرخص الإدارية لموظفي كتابة النيابة العامة بالمحكمة الابتدائية بفاس لسنة '.$current_date.'', 0, 1, 'C'); // Add title with center alignment

// Add the current date on the right side
$pdf->SetFont('arial', '', 12); // Set font to Arial Regular, size 12
$pdf->Cell(0, 10, $current_date.' ..................', 0, 1, 'R'); // Add date with right alignment
$pdf->Ln(10); // Add a line break for spacing

// column titles
$header = array('رقم التأجير', 'رقم البطاقة الوطنية', 'الإسم الشخصي', 'الإسم العائلي', 'الباقي', 'الفترة المطلوبة', 'اسم القائم بالنيابة', 'توقيع الخروج','تاريخ الدخول','توقيع الدخول');
// data loading
$data = $pdf->LoadData();

// print colored table
$pdf->ColoredTable($header, $data);

// Clear the output buffer
ob_end_clean();

// ---------------------------------------------------------

// close and output PDF document
$pdf->Output('wizaraPDF.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+

?>
