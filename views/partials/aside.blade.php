<div class="d-flex flex-column align-items-stretch">
    <h5 class="text-secondary text-uppercase fs-6 fw-semibold mb-3 px-2">Menu</h5>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item mb-1">
            <a class="nav-link text-dark <?php echo (strpos($_SERVER['REQUEST_URI'] ?? '', 'admin/bookings') !== false) ? 'bg-success text-white' : ''; ?>"
               href="<?php echo route('admin/bookings'); ?>">
                📋 Danh sách đặt sân
            </a>
        </li>
        <li class="nav-item mb-1">
            <a class="nav-link text-dark <?php echo (strpos($_SERVER['REQUEST_URI'] ?? '', 'admin/pitches') !== false) ? 'bg-success text-white' : ''; ?>"
               href="<?php echo route('admin/pitches'); ?>">
                🏟️ Quản lý sân
            </a>
        </li>
    </ul>
</div>
