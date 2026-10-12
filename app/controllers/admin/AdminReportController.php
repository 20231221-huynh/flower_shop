<?php
// app/controllers/admin/AdminReportController.php
require_once ROOT_PATH . '/core/Controller.php';

class AdminReportController extends Controller {

    // ── Shared: lấy dữ liệu báo cáo ──────────────────────────────────
    private function getReportData(string $fromDate, string $toDate): array {
        $db = Database::getInstance();

        $summary = $db->prepare("
            SELECT
              COALESCE(SUM(CASE WHEN pay_status='paid' THEN total_amount ELSE 0 END), 0) AS total_revenue,
              COUNT(*)                                                                     AS total_orders,
              SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END)                         AS cancelled_orders,
              SUM(CASE WHEN pay_status='paid' THEN 1 ELSE 0 END)                          AS paid_orders
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
        ");
        $summary->execute([$fromDate, $toDate]);
        $summary = $summary->fetch();

        $customers = $db->prepare("
            SELECT COUNT(DISTINCT user_id) AS total_customers
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ? AND pay_status = 'paid'
        ");
        $customers->execute([$fromDate, $toDate]);
        $summary['total_customers'] = (int)$customers->fetchColumn();

        $revenueByDay = $db->prepare("
            SELECT DATE(created_at) AS day,
                   COUNT(*) AS orders,
                   COALESCE(SUM(CASE WHEN pay_status='paid' THEN total_amount ELSE 0 END),0) AS revenue
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ");
        $revenueByDay->execute([$fromDate, $toDate]);
        $revenueByDay = $revenueByDay->fetchAll();

        $topProducts = $db->prepare("
            SELECT p.name, SUM(oi.quantity) AS sold,
                   SUM(oi.quantity * oi.price) AS revenue
            FROM order_items oi
            JOIN products p   ON oi.product_id = p.id
            JOIN orders o     ON oi.order_id   = o.id
            WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.pay_status = 'paid'
            GROUP BY p.id, p.name
            ORDER BY sold DESC
            LIMIT 5
        ");
        $topProducts->execute([$fromDate, $toDate]);
        $topProducts = $topProducts->fetchAll();

        return compact('summary', 'revenueByDay', 'topProducts');
    }

    // ── Hiển thị trang báo cáo ────────────────────────────────────────
    public function index(): void {
        $this->requireRole('admin');

        $fromDate = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
        $toDate   = $_GET['to']   ?? date('Y-m-d');
        if ($toDate < $fromDate) $toDate = $fromDate;

        $data = $this->getReportData($fromDate, $toDate);

        $this->render('admin/report/index', array_merge($data, [
            'title'    => 'Báo cáo doanh thu',
            'fromDate' => $fromDate,
            'toDate'   => $toDate,
        ]));
    }

    // ── Xuất Excel (.xlsx) ────────────────────────────────────────────
    public function exportExcel(): void {
        $this->requireRole('admin');

        $fromDate = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
        $toDate   = $_GET['to']   ?? date('Y-m-d');
        if ($toDate < $fromDate) $toDate = $fromDate;

        // Kiểm tra PhpSpreadsheet đã được cài chưa
        $autoload = ROOT_PATH . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            die('Lỗi: Chưa cài PhpSpreadsheet. Chạy lệnh: composer require phpoffice/phpspreadsheet');
        }
        require_once $autoload;

        $data         = $this->getReportData($fromDate, $toDate);
        $summary      = $data['summary'];
        $revenueByDay = $data['revenueByDay'];
        $topProducts  = $data['topProducts'];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Báo cáo doanh thu');

        // ── Màu sắc dùng chung ──
        $RED    = 'C0392B';
        $WHITE  = 'FFFFFF';
        $GRAY   = 'F2F2F2';
        $BORDER = 'CCCCCC';

        $Fill       = \PhpOffice\PhpSpreadsheet\Style\Fill::class;
        $Border     = \PhpOffice\PhpSpreadsheet\Style\Border::class;
        $Alignment  = \PhpOffice\PhpSpreadsheet\Style\Alignment::class;

        // Helper: áp style cho một range
        $applyStyle = function(string $range, array $style) use ($sheet) {
            $sheet->getStyle($range)->applyFromArray($style);
        };

        // Style header section (nền đỏ, chữ trắng, bold)
        $sectionStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => $WHITE], 'size' => 11],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $RED]],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ];

        // Border mỏng toàn ô
        $thinBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color'       => ['rgb' => $BORDER],
                ],
            ],
        ];

        // ══════════════════════════════════════════════════════
        // PHẦN 1 – TIÊU ĐỀ
        // ══════════════════════════════════════════════════════
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'BÁO CÁO DOANH THU');
        $applyStyle('A1:D1', [
            'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => $RED]],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A2',
            'Từ ngày: ' . date('d/m/Y', strtotime($fromDate)) .
            '   →   Đến ngày: ' . date('d/m/Y', strtotime($toDate))
        );
        $applyStyle('A2:D2', [
            'font'      => ['italic' => true, 'color' => ['rgb' => '555555']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        // ══════════════════════════════════════════════════════
        // PHẦN 2 – TỔNG QUAN
        // ══════════════════════════════════════════════════════
        $row = 4;
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("A{$row}", 'TỔNG QUAN');
        $applyStyle("A{$row}:B{$row}", array_merge($sectionStyle, $thinBorder));
        $sheet->getRowDimension($row)->setRowHeight(22);

        $summaryRows = [
            ['Doanh thu',          number_format((float)$summary['total_revenue'], 0, ',', '.') . ' ₫'],
            ['Tổng đơn hàng',      $summary['total_orders']],
            ['Đơn đã thanh toán',  $summary['paid_orders']],
            ['Đơn bị hủy',         $summary['cancelled_orders']],
            ['Khách mua hàng',     $summary['total_customers']],
        ];

        foreach ($summaryRows as $i => [$label, $value]) {
            $r = $row + 1 + $i;
            $sheet->setCellValue("A{$r}", $label);
            $sheet->setCellValue("B{$r}", $value);
            $bg = ($i % 2 === 0) ? $GRAY : $WHITE;
            $applyStyle("A{$r}:B{$r}", array_merge($thinBorder, [
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
            ]));
        }

        // ══════════════════════════════════════════════════════
        // PHẦN 3 – DOANH THU THEO NGÀY
        // ══════════════════════════════════════════════════════
        $row = $row + count($summaryRows) + 3;
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", 'DOANH THU THEO NGÀY');
        $applyStyle("A{$row}:C{$row}", array_merge($sectionStyle, $thinBorder));
        $sheet->getRowDimension($row)->setRowHeight(22);

        // Header bảng
        $row++;
        $sheet->setCellValue("A{$row}", 'Ngày');
        $sheet->setCellValue("B{$row}", 'Số đơn');
        $sheet->setCellValue("C{$row}", 'Doanh thu (₫)');
        $applyStyle("A{$row}:C{$row}", array_merge($thinBorder, [
            'font'      => ['bold' => true, 'color' => ['rgb' => $WHITE]],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '555555']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]));

        foreach ($revenueByDay as $i => $r) {
            $row++;
            $sheet->setCellValue("A{$row}", date('d/m/Y', strtotime($r['day'])));
            $sheet->setCellValue("B{$row}", (int)$r['orders']);
            $sheet->setCellValue("C{$row}", (float)$r['revenue']);
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("B{$row}")->getAlignment()
                  ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $bg = ($i % 2 === 0) ? $WHITE : $GRAY;
            $applyStyle("A{$row}:C{$row}", array_merge($thinBorder, [
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
            ]));
        }

        // Dòng tổng cộng
        $row++;
        $totalOrders  = array_sum(array_column($revenueByDay, 'orders'));
        $totalRevenue = array_sum(array_column($revenueByDay, 'revenue'));
        $sheet->setCellValue("A{$row}", 'Tổng cộng');
        $sheet->setCellValue("B{$row}", $totalOrders);
        $sheet->setCellValue("C{$row}", $totalRevenue);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $applyStyle("A{$row}:C{$row}", array_merge($thinBorder, [
            'font'      => ['bold' => true],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFE4E1']],
        ]));
        $sheet->getStyle("B{$row}")->getAlignment()
              ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // ══════════════════════════════════════════════════════
        // PHẦN 4 – TOP 5 SẢN PHẨM BÁN CHẠY
        // ══════════════════════════════════════════════════════
        $row += 3;
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", 'TOP 5 SẢN PHẨM BÁN CHẠY');
        $applyStyle("A{$row}:D{$row}", array_merge($sectionStyle, $thinBorder));
        $sheet->getRowDimension($row)->setRowHeight(22);

        $row++;
        foreach (['A' => 'Hạng', 'B' => 'Sản phẩm', 'C' => 'Đã bán', 'D' => 'Doanh thu (₫)'] as $col => $heading) {
            $sheet->setCellValue("{$col}{$row}", $heading);
        }
        $applyStyle("A{$row}:D{$row}", array_merge($thinBorder, [
            'font'      => ['bold' => true, 'color' => ['rgb' => $WHITE]],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '555555']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]));

        foreach ($topProducts as $i => $p) {
            $row++;
            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $p['name']);
            $sheet->setCellValue("C{$row}", (int)$p['sold']);
            $sheet->setCellValue("D{$row}", (float)$p['revenue']);
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $bg = ($i % 2 === 0) ? $WHITE : $GRAY;
            $applyStyle("A{$row}:D{$row}", array_merge($thinBorder, [
                'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ]));
            $sheet->getStyle("B{$row}")->getAlignment()
                  ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        }

        // ── Độ rộng cột ──
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(32);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(22);

        // ── Xuất file ──
        $filename = 'bao-cao-doanh-thu_' . $fromDate . '_' . $toDate . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ── Xuất PDF ──────────────────────────────────────────────────────
    public function exportPdf(): void {
        $this->requireRole('admin');

        $fromDate = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
        $toDate   = $_GET['to']   ?? date('Y-m-d');
        if ($toDate < $fromDate) $toDate = $fromDate;

        $data = $this->getReportData($fromDate, $toDate);
        $summary      = $data['summary'];
        $revenueByDay = $data['revenueByDay'];
        $topProducts  = $data['topProducts'];

        $filename = 'bao-cao-doanh-thu_' . $fromDate . '_' . $toDate . '.html';

        // Xuất dưới dạng HTML có thể in (print-to-PDF từ trình duyệt)
        // Hoặc dùng thư viện DOMPDF/mPDF nếu đã cài đặt
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: inline; filename="' . $filename . '"');

        $totalOrders  = array_sum(array_column($revenueByDay, 'orders'));
        $totalRevenue = array_sum(array_column($revenueByDay, 'revenue'));

        echo '<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Báo cáo doanh thu</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 13px; color: #222; background: #fff; padding: 30px; }
  h1 { font-size: 20px; text-align: center; color: #c0392b; margin-bottom: 4px; }
  .subtitle { text-align: center; color: #555; font-size: 12px; margin-bottom: 24px; }
  .section-title { font-size: 14px; font-weight: bold; color: #c0392b; margin: 20px 0 8px;
                   border-bottom: 2px solid #c0392b; padding-bottom: 4px; }
  .summary-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 8px; }
  .summary-card { flex: 1 1 140px; border: 1px solid #ddd; border-radius: 6px; padding: 10px 14px;
                  background: #fafafa; }
  .summary-card .label { font-size: 11px; color: #777; }
  .summary-card .value { font-size: 16px; font-weight: bold; color: #c0392b; margin-top: 2px; }
  table { width: 100%; border-collapse: collapse; margin-top: 6px; }
  th { background: #c0392b; color: #fff; padding: 7px 10px; text-align: left; font-size: 12px; }
  td { padding: 6px 10px; border-bottom: 1px solid #eee; font-size: 12px; }
  tr:last-child td { border-bottom: none; }
  tfoot td { font-weight: bold; background: #f5f5f5; }
  .text-right { text-align: right; }
  .text-center { text-align: center; }
  .print-btn { display: block; margin: 0 auto 20px; padding: 10px 30px; background: #c0392b;
               color: #fff; border: none; border-radius: 6px; font-size: 14px; cursor: pointer; }
  @media print { .print-btn { display: none; } body { padding: 10px; } }
  .generated { text-align: right; font-size: 11px; color: #999; margin-top: 20px; }
</style>
</head>
<body>
<button class="print-btn" onclick="window.print()">🖨️ In / Lưu PDF</button>

<h1>BÁO CÁO DOANH THU</h1>
<p class="subtitle">Từ ngày ' . date('d/m/Y', strtotime($fromDate)) . ' đến ngày ' . date('d/m/Y', strtotime($toDate)) . '</p>

<div class="section-title">TỔNG QUAN</div>
<div class="summary-grid">
  <div class="summary-card"><div class="label">💰 Doanh thu</div><div class="value">' . number_format((float)$summary['total_revenue'], 0, ',', '.') . ' ₫</div></div>
  <div class="summary-card"><div class="label">📦 Tổng đơn hàng</div><div class="value">' . $summary['total_orders'] . '</div></div>
  <div class="summary-card"><div class="label">✅ Đơn đã thanh toán</div><div class="value">' . $summary['paid_orders'] . '</div></div>
  <div class="summary-card"><div class="label">❌ Đơn bị hủy</div><div class="value">' . $summary['cancelled_orders'] . '</div></div>
  <div class="summary-card"><div class="label">👥 Khách mua hàng</div><div class="value">' . $summary['total_customers'] . '</div></div>
</div>';

        if (!empty($revenueByDay)) {
            echo '<div class="section-title">DOANH THU THEO NGÀY</div>
<table>
  <thead><tr><th>Ngày</th><th class="text-center">Số đơn</th><th class="text-right">Doanh thu (₫)</th></tr></thead>
  <tbody>';
            foreach ($revenueByDay as $row) {
                echo '<tr>
    <td>' . date('d/m/Y', strtotime($row['day'])) . '</td>
    <td class="text-center">' . $row['orders'] . '</td>
    <td class="text-right">' . number_format((float)$row['revenue'], 0, ',', '.') . ' ₫</td>
  </tr>';
            }
            echo '</tbody>
  <tfoot><tr>
    <td>Tổng cộng</td>
    <td class="text-center">' . $totalOrders . '</td>
    <td class="text-right">' . number_format($totalRevenue, 0, ',', '.') . ' ₫</td>
  </tr></tfoot>
</table>';
        }

        if (!empty($topProducts)) {
            echo '<div class="section-title">TOP 5 SẢN PHẨM BÁN CHẠY</div>
<table>
  <thead><tr><th>Hạng</th><th>Sản phẩm</th><th class="text-center">Đã bán</th><th class="text-right">Doanh thu (₫)</th></tr></thead>
  <tbody>';
            foreach ($topProducts as $i => $p) {
                echo '<tr>
    <td class="text-center">' . ($i + 1) . '</td>
    <td>' . htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') . '</td>
    <td class="text-center">' . $p['sold'] . '</td>
    <td class="text-right">' . number_format((float)$p['revenue'], 0, ',', '.') . ' ₫</td>
  </tr>';
            }
            echo '</tbody>
</table>';
        }

        echo '<p class="generated">Báo cáo được tạo lúc: ' . date('H:i:s d/m/Y') . '</p>
</body></html>';
        exit;
    }
}