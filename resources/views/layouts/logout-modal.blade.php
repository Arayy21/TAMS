<div class="modal fade" id="modalLogout" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <i class="bi bi-box-arrow-right fs-1 text-danger"></i>
                <h5 class="mt-2 mb-1">Keluar dari TAMS?</h5>
                <p class="text-muted small mb-4">Anda perlu masuk kembali untuk mengakses data aset.</p>

                <form method="POST" action="{{ route('logout') }}" class="d-flex gap-2 justify-content-center">
                    @csrf
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Ya, Keluar</button>
                </form>
            </div>
        </div>
    </div>
</div>