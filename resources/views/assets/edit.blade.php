@extends('layouts.app')
@section('title', 'Edit Aset ' . $asset->asset_code)

@section('content')
<div class="card-tams p-4" style="max-width:860px">
    <form method="POST" action="{{ route('assets.update', $asset) }}" enctype="multipart/form-data">
        @method('PUT')
        @include('assets._form')
    </form>
</div>
@endsection