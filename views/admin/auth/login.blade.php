<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập quản trị</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 fw-bold text-center mb-4">Đăng nhập quản trị</h1>

                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?php echo route('admin/login'); ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Tên đăng nhập</label>
                            <input id="username" type="text" name="username" class="form-control" required autofocus
                                   autocomplete="username"
                                   value="<?php echo htmlspecialchars($old['username'] ?? ''); ?>">
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Mật khẩu</label>
                            <input id="password" type="password" name="password" class="form-control" required
                                   autocomplete="current-password">
                        </div>
                        <button type="submit" class="btn btn-success w-100">Đăng nhập</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
