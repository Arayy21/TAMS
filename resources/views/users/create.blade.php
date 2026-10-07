@extends('layouts.app')
@section('title', 'Tambah Akun')

@section('content')
<nav class="small text-muted mb-3">
    <a href="{{ route('users.index') }}" class="text-decoration-none">Kelola Pengguna</a> / Tambah Akun
</nav>

<div class="card-tams p-4" style="max-width:860px">
    <form method="POST" action="{{ route('users.store') }}">
        @include('users._form')
    </form>
</div>
@endsection