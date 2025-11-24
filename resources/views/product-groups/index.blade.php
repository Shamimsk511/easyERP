@extends('adminlte::page')

@section('title', 'Product Groups')

@section('content_header')
    <h1>Product Groups</h1>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.16/themes/default/style.min.css" />
    <style>
        .jstree-default .jstree-clicked {
            background: #007bff;
            border-radius: 2px;
        }
        
        .jstree-default .jstree-hovered {
            background: #e9ecef;
            border-radius: 2px;
        }
        
        #groups-tree {
            padding: 15px;
        }
        
        .tree-actions {
            margin-top: 10px;
        }
        
        .jstree-default .jstree-disabled {
            opacity: 0.5;
        }
        
        .tree-search {
            margin-bottom: 15px;
        }
    </style>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Product Groups Tree</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-success btn-sm" id="add-root-btn">
                    <i class="fas fa-plus"></i> Add Root Group
                </button>
                <button type="button" class="btn btn-info btn-sm" id="expand-all-btn">
                    <i class="fas fa-expand-alt"></i> Expand All
                </button>
                <button type="button" class="btn btn-secondary btn-sm" id="collapse-all-btn">
                    <i class="fas fa-compress-alt"></i> Collapse All
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="tree-search">
                <input type="text" id="tree-search" class="form-control" placeholder="Search groups...">
            </div>
            <div id="groups-tree"></div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="groupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Product Group</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="groupForm">
                    <div class="modal-body">
                        <input type="hidden" id="group-id">
                        <input type="hidden" id="parent-id">
                        
                        <div class="form-group">
                            <label for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" checked>
                                <label class="custom-control-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.16/jstree.min.js"></script>
<script>
    $(document).ready(function() {
        let tree;
        let searchTimeout = false;

        // Initialize jsTree
        function initTree() {
            $('#groups-tree').jstree({
                'core': {
                    'data': {
                        'url': "{{ route('product-groups.index') }}",
                        'dataType': 'json'
                    },
                    'check_callback': true,
                    'themes': {
                        'responsive': true,
                        'variant': 'large'
                    }
                },
                'plugins': ['contextmenu', 'search', 'state', 'types'],
                'contextmenu': {
                    'items': customMenu
                },
                'types': {
                    'default': {
                        'icon': 'fas fa-folder text-warning'
                    }
                }
            });

            tree = $('#groups-tree').jstree(true);
        }

        // Custom context menu
        function customMenu(node) {
            return {
                'add': {
                    'label': 'Add Child Group',
                    'icon': 'fas fa-plus text-success',
                    'action': function() {
                        openModal('add', node.id);
                    }
                },
                'edit': {
                    'label': 'Edit Group',
                    'icon': 'fas fa-edit text-primary',
                    'action': function() {
                        openModal('edit', node.id);
                    }
                },
                'delete': {
                    'label': 'Delete Group',
                    'icon': 'fas fa-trash text-danger',
                    'action': function() {
                        deleteGroup(node.id);
                    }
                }
            };
        }

        // Search functionality
        $('#tree-search').on('keyup', function() {
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            searchTimeout = setTimeout(function() {
                tree.search($('#tree-search').val());
            }, 250);
        });

        // Expand/Collapse all
        $('#expand-all-btn').on('click', function() {
            tree.open_all();
        });

        $('#collapse-all-btn').on('click', function() {
            tree.close_all();
        });

        // Add root group
        $('#add-root-btn').on('click', function() {
            openModal('add', null);
        });

        // Open modal for add/edit
        function openModal(action, nodeId) {
            $('#groupForm')[0].reset();
            $('#group-id').val('');
            $('#parent-id').val('');

            if (action === 'add') {
                $('#modalTitle').text(nodeId ? 'Add Child Group' : 'Add Root Group');
                $('#parent-id').val(nodeId);
                $('#is_active').prop('checked', true);
            } else {
                $('#modalTitle').text('Edit Group');
                loadGroupData(nodeId);
            }

            $('#groupModal').modal('show');
        }

        // Load group data for editing
        function loadGroupData(id) {
            $.get("{{ url('product-groups') }}/" + id + "/edit", function(response) {
                // You'll need to return JSON data from edit route
                $('#group-id').val(id);
                // Populate other fields based on your response
            });
        }

        // Form submission
        $('#groupForm').on('submit', function(e) {
            e.preventDefault();
            
            const groupId = $('#group-id').val();
            const url = groupId 
                ? "{{ url('product-groups') }}/" + groupId 
                : "{{ route('product-groups.store') }}";
            const method = groupId ? 'PUT' : 'POST';

            const formData = {
                name: $('#name').val(),
                parent_id: $('#parent-id').val(),
                description: $('#description').val(),
                is_active: $('#is_active').is(':checked') ? 1 : 0,
                _token: "{{ csrf_token() }}"
            };

            if (method === 'PUT') {
                formData._method = 'PUT';
            }

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    Swal.fire('Success!', response.message, 'success');
                    $('#groupModal').modal('hide');
                    tree.refresh();
                },
                error: function(xhr) {
                    let message = 'An error occurred';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    Swal.fire('Error!', message, 'error');
                }
            });
        });

        // Delete group
        function deleteGroup(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('product-groups') }}/" + id,
                        type: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            Swal.fire('Deleted!', response.message, 'success');
                            tree.refresh();
                        },
                        error: function(xhr) {
                            Swal.fire('Error!', xhr.responseJSON.message, 'error');
                        }
                    });
                }
            });
        }

        // Initialize tree
        initTree();
    });
</script>
@stop
