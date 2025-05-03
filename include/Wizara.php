<?php
function generatePDF() {
    ob_start(); // Start output buffering

    // Include the main TCPDF library (search for installation path).
    require_once('../tcpdf-main/tcpdf.php');

    // Extend TCPDF with custom functions
    class MYPDF extends TCPDF {

        // Load table data from file
        public function LoadData() {
            include('dbConn.php'); // Assuming dbConn.php connects to your database
            $sql = "
            SELECT 
                users.Nbr_Financier, 
                users.CIN, 
                users.Prenom, 
                users.Nom, 
                users.Metier, 
                conge.Duree_Conge, 
                conge.Date_Debut, 
                CONCAT(vice.nom, ' ', vice.prenom) AS vice_nom_prenom,
                'موافقة' AS agreeing
            FROM 
                users
             JOIN 
                conge 
            ON 
                users.id_user = conge.id_user
             JOIN
                users AS vice
            ON
                conge.id_user = vice.id_user;
            ";
        
            $stmt = $dbh->prepare($sql); // Assuming $dbh is your PDO connection
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
            $w = array(20, 25, 30, 35, 40, 20, 30, 30, 20);
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
                $this->MultiCell($w[4], 18, $row["Metier"], 'LR', 'C', $fill, 0, '', '', true);
                $this->MultiCell($w[5], 18, $row["Duree_Conge"], 'LR', 'C', $fill, 0, '', '', true);
                $this->MultiCell($w[6], 18, $row["Date_Debut"], 'LR', 'C', $fill, 0, '', '', true);
                $this->MultiCell($w[7], 18, $row["vice_nom_prenom"], 'LR', 'C', $fill, 0, '', '', true);
                $this->MultiCell($w[8], 18, $row["agreeing"], 'LR', 'C', $fill, 0, '', '', true);
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

    // Create new PDF document with A4 landscape orientation
    $pdf = new MYPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Nicola Asuni');
    $pdf->SetTitle('TCPDF Example 011');
    $pdf->SetSubject('TCPDF Tutorial');
    $pdf->SetKeywords('TCPDF, PDF, example, test, guide');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(true); // Enable footer

    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // Set some language-dependent strings (optional)
    if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
        require_once(dirname(__FILE__).'/lang/eng.php');
        $pdf->setLanguageArray($l);
    }

    // Set RTL direction
    $pdf->setRTL(true);

    // Add a page
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

    // Column titles
    $header = array('رقم التأجير', 'رقم البطاقة الوطنية', 'الإسم الشخصي', 'الإسم العائلي', 'الإطار', 'مدة الرخصة', 'الفترة المطلوبة', 'اسم القائم بالنيابة', 'الموافقة عليه من طرف الرئيس');
    // Data loading
    $data = $pdf->LoadData();

    // Print colored table
    $pdf->ColoredTable($header, $data);

    // Clear the output buffer
    ob_end_clean();

    // Close and output PDF document
    $pdf->Output('wizaraPDF.pdf', 'I');
}

// Generate PDF
generatePDF();
?>
