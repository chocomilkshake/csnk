<?php

$this->data = '
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
' . file_get_contents(__DIR__ . '/../HTML/smc.css') . '
</style>
</head>
<body>

<!-- ================= HEADER ================= -->
<table class="header">
  <tr>
    <td class="header-left">
      <img src="http://localhost/csnk/resources/img/smcbrandname.png" class="logo-main">
      <div class="company-name">SMC Agency</div>
      <div class="address">
        SMC International Recruitment<br>
        Makati City, Philippines
      </div>
    </td>

    <td class="header-right">
      <img src="http://localhost/csnk/resources/img/smc.png" class="logo-icon">
    </td>
  </tr>
</table>

<!-- ================= TITLE ================= -->
<div class="title">SMC Manpower Agency Philippines Co. (Invoice)</div> <br>

<!-- ================= META ================= -->
<table class="meta">
  <tr>
    <td width="60%">
      <strong>Billed To:</strong><br>';

foreach ($this->billto as $b) {
    $this->data .= $b . '<br>';
}

$this->data .= '
    </td>

    <td width="40%" class="meta-right">';

foreach ($this->head as $h) {
    $this->data .= '<div><strong>' . $h[0] . ':</strong> ' . $h[1] . '</div>';
}

$this->data .= '
    </td>
  </tr>
</table>

<!-- ================= ITEMS ================= -->
<table class="items">
  <tr>
    <th>Applicant Name</th>
    <th>Work Duration</th>
    <th>No. Days</th>
    <th class="right">Service Fee</th>
  </tr>';

foreach ($this->items as $i) {
    $this->data .= '
    <tr>
      <td>' . $i[0] . '</td>
      <td>' . $i[1] . '</td>
      <td class="center">' . $i[2] . '</td>
      <td class="right">' . $i[3] . '</td>
    </tr>';
}

foreach ($this->totals as $t) {
    $this->data .= '
    <tr class="total">
      <td colspan="3" class="right">' . $t[0] . '</td>
      <td class="right">' . $t[1] . '</td>
    </tr>';
}

$this->data .= '
</table>

<!-- ================= FOOTER ================= -->
<div class="note">
  I declare that all information contained in this invoice are certified true and correct.
</div>

<div class="payment">
  <strong>Issued By:</strong> SMC Agency<br><br>
  <strong>Payment Method:</strong><br>
  Bank Transfer / International Remittance
</div>

</body>
</html>
';

$mpdf->WriteHTML($this->data);