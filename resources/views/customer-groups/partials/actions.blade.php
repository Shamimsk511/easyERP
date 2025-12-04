<div class="btn-group">
    <a href="{{ route('customer-groups.edit', $group->id) }}" class="btn btn-sm btn-primary" title="Edit">
        <i class="fas fa-edit"></i>
    </a>
    <a href="{{ route('customers.index', ['group_id' => $group->id]) }}" 
       class="btn btn-sm btn-info" title="View Customers">
        <i class="fas fa-users"></i>
    </a>
    <button type="button" class="btn btn-sm btn-danger btn-delete" 
            data-group-id="{{ $group->id }}" 
            data-group-name="{{ $group->name }}"
            title="Delete">
        <i class="fas fa-trash"></i>
    </button>
</div>
