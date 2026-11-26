<?php
require_once __DIR__ . '/../../config/config.php';
$pageTitle = "Tentang Kami";
include __DIR__ . '/../../includes/header.php';
?>

<div class="about-wrap">
  <div class="about-img">
    <img src="<?= BASE_URL ?>/assets/img/AyamGeprek.jpg" alt="Ayam Geprek Mantul" style="width: 340px; max-width: 100%; height: auto; border-radius: 14px; object-fit: cover; flex-shrink: 0;">
  </div>

  <div class="about-text">
    <p>
      <strong>Geprek Mantul</strong> Kami adalah usaha ayam geprek yang baru dirintis dengan tujuan menghadirkan makanan yang enak, terjangkau, dan cocok dinikmati oleh siapa saja. Kami menyajikan ayam geprek dengan ayam yang renyah, sambal yang dibuat dengan cita rasa khas, serta pilihan tingkat kepedasan yang bisa disesuaikan dengan selera pelanggan.
    </p>
    <p>
      Sebagai usaha yang masih berkembang, kami terus berusaha menjaga kualitas rasa dan pelayanan agar setiap pelanggan mendapatkan pengalaman makan yang menyenangkan. Kami percaya bahwa dari usaha kecil ini, kami dapat terus berkembang dan menjadi salah satu pilihan favorit untuk menikmati ayam geprek.
    <p>
      Terima kasih sudah mendukung perjalanan usaha kami. Yuk, cobain ayam geprek kami dan rasakan sendiri cita rasanya!
    </p>
  </div>
</div>

<div class="value-grid">
  <div class="value-card">
    <div class="icon">🎯</div>
    <h3>Visi Kami</h3>
    <p>
      Menjadi usaha ayam geprek yang dikenal luas karena rasa yang lezat, harga yang terjangkau, serta pelayanan yang ramah dan berkualitas.
    </p>
  </div>

  <div class="value-card">
    <div class="icon">🤝</div>
    <h3>Misi Kami</h3>
    <p>
      Menyediakan ayam geprek dengan bahan yang berkualitas dan rasa yang konsisten, memberikan harga yang terjangkau, serta memberikan pelayanan yang ramah, cepat, dan praktis. Kami juga terus berinovasi untuk mengembangkan usaha agar dapat memenuhi kebutuhan dan selera pelanggan.
    </p>
  </div>

  <div class="value-card">
    <div class="icon">🌟</div>
    <h3>Nilai Kami</h3>
    <p>
      Kami mengutamakan kualitas, kejujuran, dan kepuasan pelanggan. Kami selalu menjaga rasa dan kualitas makanan serta memberikan pelayanan yang ramah. Kami juga berkomitmen untuk terus berkembang dan memberikan yang terbaik bagi pelanggan.
    </p>
  </div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
