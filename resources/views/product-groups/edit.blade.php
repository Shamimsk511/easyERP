@extends('adminlte::page')

@section('title', 'Edit Product Group')

@section('content_header')
    <h1>Edit Product Group</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Group Information</h3>
        </div>
        
        <form action="{{ route('product-groups.update', $productGroup->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Group Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $productGroup->name) }}"
                                   required>
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="parent_id">Parent Group</label>
                            <select class="form-control select2 @error('parent_id') is-invalid @enderror" 
                                    id="parent_id" 
                                    name="parent_id"
                                    style="width: 100%;">
                                <option value="">-- No Parent (Root Group) --</option>
                                @foreach($parentGroups as $group)
                                    <option value="{{ $group->id }}" {{ old('parent_id', $productGroup->parent_id) == $group->id ? 'selected' : '' }}>
                                        {{ $group->full_path }}
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" 
                              id="description" 
                              name="description" 
                              rows="3">{{ old('description', $productGroup->description) }}</textarea>
                    @error('description')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-check">
                    <input type="checkbox" 
                           class="form-check-input" 
                           id="is_active" 
                           name="is_active" 
                           value="1"
                           {{ old('is_active', $productGroup->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Group
                </button>
                <a href="{{ route('product-groups.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
@stop

@section('js')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap4',
            placeholder: '-- Select Parent Group --',
            allowClear: true
        });
    });
</script>
@stop
