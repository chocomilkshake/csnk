<table style="width:100%; border-collapse:collapse;">
  <tr>
    <td style="vertical-align:top;">
      <img src="../../resources/img/whychoose.png" style="height:55px;" alt="CSNK Logo"><br>
      <div style="font-size:10px; color:#444; margin-top:4px;">
        Unit 1 Eden Townhomes 2001 Eden Street corner<br>
        Pedro Gil Street Sta. Ana, Manila, Philippines
      </div>
    </td>
    <td style="vertical-align:top; text-align:right;">
      <img src="../../resources/img/csnk-iconz.png" style="height:55px;" alt="CSNK Logo">
    </td>

  </tr>
</table>

<div style="
  text-align:center;
  font-size:22px;
  letter-spacing:3px;
  margin:22px 0;
  font-weight:bold;
">
  INVOICE
</div>

<table style="width:100%; margin-bottom:15px;">
  <tr>
    <td style="width:60%; font-size:11px;">
      <strong>Billed to:</strong><br>
      <?php foreach ($this->billto as $b) echo $b . "<br>"; ?>
    </td>
    <td style="width:40%; font-size:11px; text-align:right;">
      <?php foreach ($this->head as $h): ?>
        <div><strong><?= $h[0] ?>:</strong> <?= $h[1] ?></div>
      <?php endforeach; ?>
    </td>
  </tr>
</table>

<table style="width:100%; border-collapse:collapse; font-size:11px;">
  <thead>
    <tr>
      <th style="background:#f2f2f2; border-bottom:2px solid #000; padding:6px;">Applicant Name</th>
      <th style="background:#f2f2f2; border-bottom:2px solid #000; padding:6px;">Work Duration</th>
      <th style="background:#f2f2f2; border-bottom:2px solid #000; padding:6px;">No. Days</th>
      <th style="background:#f2f2f2; border-bottom:2px solid #000; padding:6px; text-align:right;">Service Fee</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($this->items as $i): ?>
      <tr>
        <td style="padding:6px; border-bottom:1px solid #ccc;"><?= $i[0] ?></td>
        <td style="padding:6px; border-bottom:1px solid #ccc;"><?= $i[1] ?: '—' ?></td>
        <td style="padding:6px; border-bottom:1px solid #ccc;"><?= $i[2] ?></td>
        <td style="padding:6px; border-bottom:1px solid #ccc; text-align:right;"><?= $i[4] ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
  <tfoot>
    <?php foreach ($this->totals as $t): ?>
      <tr>
        <td colspan="3" style="padding-top:8px; border-top:2px solid #000; text-align:right; font-weight:bold;">
          <?= $t[0] ?>
        </td>
        <td style="padding-top:8px; border-top:2px solid #000; text-align:right; font-weight:bold;">
          <?= $t[1] ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tfoot>
</table>

<div style="margin-top:18px; font-size:11px;">
  I declare that all information contained in this invoice are certified true and correct.
</div>

<div style="margin-top:22px; font-size:11px;">
  <strong>Issued By:</strong> CSNK Agency<br><br>
  <strong>Payment Method:</strong><br>
  GCASH: 091-0000-0000<br>
  Bank Transfer: RCBC acc no: 1234-1234-1234-1234
</div>