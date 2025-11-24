@extends('adminlte::page')

@section('title', 'Create Unit')

@section('content_header')
    <h1>Create New Unit</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Unit Information</h3>
        </div>
        
        <form action="{{ route('units.store') }}" method="POST">
            @csrf
            
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Unit Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   placeholder="e.g., Square Feet, Pieces, Box"
                                   value="{{ old('name') }}"
                                   required>
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="symbol">Symbol <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('symbol') is-invalid @enderror" 
                                   id="symbol" 
                                   name="symbol" 
                                   placeholder="e.g., sft, pcs, box"
                                   value="{{ old('symbol') }}"
                                   required>
                            @error('symbol')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="type">Type <span class="text-danger">*</span></label>
                            <select class="form-control @error('type') is-invalid @enderror" 
                                    id="type" 
                                    name="type" 
                                    required>
                                <option value="">Select Type</option>
                                <option value="simple" {{ old('type') == 'simple' ? 'selected' : '' }}>Simple</option>
                                <option value="compound" {{ old('type') == 'compound' ? 'selected' : '' }}>Compound</option>
                            </select>
                            @error('type')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="form-check">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       id="is_base_unit" 
                                       name="is_base_unit" 
                                       value="1"
                                       {{ old('is_base_unit') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_base_unit">
                                    Can be used as Base Unit
                                </label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       id="is_active" 
                                       name="is_active" 
                                       value="1"
                                       {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" 
                              id="description" 
                              name="description" 
                              rows="3" 
                              placeholder="Optional description">{{ old('description') }}</textarea>
                    @error('description')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Unit
                </button>
                <a href="{{ route('units.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
@stop
