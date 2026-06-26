<header class="topbar bg-white border-bottom">
    <div class="container-fluid px-4 py-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">
                    @if(auth()->user()->isGKM())
                        Sistem Administrasi GKM - {{ auth()->user()->dosen->prodi->nama_prodi ?? 'Prodi' }}
                    @elseif(auth()->user()->isGJM())
                        Sistem Administrasi GJM - FAKULTAS VOKASI
                    @else
                        Dashboard
                    @endif
                </h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-sm btn-light position-relative">
                    <i class="bi bi-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        3
                    </span>
                </button>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#">Pengaturan Profil</a></li>
                        <li><a class="dropdown-item" href="#">Bantuan</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item">Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>

<style>
.topbar {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.main-content {
    padding: 2rem;
    background-color: #f8f9fa;
    min-height: calc(100vh - 70px);
}
</style>
