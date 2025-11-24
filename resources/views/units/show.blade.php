@extends('adminlte::page')

@section('title', 'View Unit')

@section('content_header')
    <h1>Unit Details</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $unit->name }}</h3>
            <div class="card-tools">
                <a href="{{ route('units.edit', $unit->id) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="{{ route('units.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th style="width: 200px;">ID</th>
                        <td>{{ $unit->id }}</td>
                    </tr>
                    <tr>
                        <th>Name</th>
                        <td>{{ $unit->name }}</td>
                    </tr>
                    <tr>
                        <th>Symbol</th>
                        <td><code>{{ $unit->symbol }}</code></td>
                    </tr>
                    <tr>
                        <th>Type</th>
                        <td><span class="badge badge-info">{{ ucfirst($unit->type) }}</span></td>
                    </tr>
                    <tr>
                        <th>Base Unit</th>
                        <td>
                            @if($unit->is_base_unit)
                                <span class="badge badge-success">Yes</span>
                            @else
                                <span class="badge badge-secondary">No</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($unit->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td>{{ $unit->description ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Created At</th>
                        <td>{{ $unit->created_at->format('d M Y, h:i A') }}</td>
                    </tr>
                    <tr>
                        <th>Updated At</th>
                        <td>{{ $unit->updated_at->format('d M Y, h:i A') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@stop
