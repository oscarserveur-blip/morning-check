@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Nouvelle catégorie</h5>
                </div>

                <div class="card-body">
                    @include('categories.partials.create-form')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 