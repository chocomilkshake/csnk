<?php

$this->data = '
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
' . file_get_contents(__DIR__ . '/../HTML/csnk.css') . '
</style>
</head>
<body>

<!-- ================= HEADER ================= -->
<table class="header">
  <tr>
    <td class="header-left">
      <img src="http://localhost/csnk/resources/img/whychoose.png" class="logo-main">
      <div class="company-name">CSNK Agency</div>
      <div class="address">
        Unit 1 Eden Townhomes 2001 Eden Street corner<br>
        Pedro Gil Street Sta. Ana, Manila, Philippines
      </div>
    </td>

    <td class="header-right">
      <img src="http://localhost/csnk/resources/img/csnk-iconz.png" class="logo-icon">
    </td>
  </tr>
</table>

<!-- ================= TITLE ================= -->
<div class="title">INVOICE</div>

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
  <strong>Issued By:</strong> CSNK Agency<br><br>

  <strong>Payment Method:</strong><br>
  GCASH: 091-0000-0000<br>
  Bank Transfer: RCBC acc no: 1234-1234-1234-1234
</div>

</body>
</html>
';

$mpdf->WriteHTML($this->data);