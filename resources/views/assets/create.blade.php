@extends('layouts.app')
@section('title', 'Tambah Aset')

@section('content')
<div class="card-tams p-4" style="max-width:860px">
    <form method="POST" action="{{ route('assets.store') }}">
        @include('assets._form')
    </form>
</div>
@endsection