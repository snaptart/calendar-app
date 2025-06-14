/**
 * Programs Page Configuration for CrudPageFactory
 * Location: frontend/js/config/programs-page-config.js
 */

import { Utils } from '../core/utils.js';
import { APIClient } from '../core/api-client.js';

export const ProgramsPageConfig = {
    entityType: 'programs',
    apiAction: 'programs_with_stats',
    tableId: 'programsTable',
    
    columns: [
        { 
            data: 'color', 
            title: 'Color',
            name: 'color',
            width: '60px',
            orderable: false,
            searchable: false,
            render: (data, type, row) => {
                const color = data || '#3498db';
                return `<div class="color-indicator" style="background-color: ${color}; width: 20px; height: 20px; border-radius: 50%; display: inline-block;"></div>`;
            }
        },
        { 
            data: 'name', 
            title: 'Program Name',
            name: 'name',
            render: (data, type, row) => data || 'Unnamed Program'
        },
        { 
            data: 'program_type_name', 
            title: 'Type',
            name: 'program_type',
            render: (data) => {
                if (!data) return '<span class="badge badge-secondary">Unknown</span>';
                const typeClass = {
                    'Youth Hockey': 'badge-primary',
                    'Adult Hockey': 'badge-info', 
                    'Figure Skating': 'badge-purple',
                    'Learn to Skate': 'badge-success',
                    'Speed Skating': 'badge-warning',
                    'Curling': 'badge-dark'
                }[data] || 'badge-secondary';
                return `<span class="badge ${typeClass}">${data}</span>`;
            }
        },
        { 
            data: 'facility_name', 
            title: 'Facility',
            name: 'facility',
            render: (data) => data || 'No Facility'
        },
        { 
            data: 'contact_name', 
            title: 'Contact',
            name: 'contact',
            render: (data, type, row) => {
                if (!data) return 'No Contact';
                const email = row.contact_email;
                return email ? `<a href="mailto:${email}">${data}</a>` : data;
            }
        },
        { 
            data: 'status', 
            title: 'Status',
            name: 'status',
            render: (data) => {
                const statusClass = data === 'Active' ? 'badge-success' : 'badge-secondary';
                return `<span class="badge ${statusClass}">${data || 'Active'}</span>`;
            }
        },
        { 
            data: 'episode_count', 
            title: 'Episodes',
            name: 'episodes',
            render: (data) => data || '0'
        },
        { 
            data: 'team_count', 
            title: 'Teams',
            name: 'teams',
            render: (data) => data || '0'
        },
        { 
            data: 'created_at', 
            title: 'Created',
            name: 'created',
            render: (data) => Utils.formatDateOnly(data)
        },
        {
            data: null,
            title: 'Actions',
            orderable: false,
            searchable: false,
            width: '120px',
            render: (data, type, row) => {
                return `
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary view-btn" data-id="${row.id}">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary edit-btn" data-id="${row.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-id="${row.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
            }
        }
    ],
    
    // Form fields for create/edit modal
    formFields: [
        {
            name: 'name',
            label: 'Program Name',
            type: 'text',
            required: true,
            placeholder: 'Enter program name'
        },
        {
            name: 'program_type_id',
            label: 'Program Type',
            type: 'select',
            required: true,
            options: async () => {
                try {
                    const response = await APIClient.get('program_types');
                    return response.map(type => ({
                        value: type.id,
                        label: type.name
                    }));
                } catch (error) {
                    console.error('Error loading program types:', error);
                    return [];
                }
            },
            placeholder: 'Select program type'
        },
        {
            name: 'facility_id',
            label: 'Facility',
            type: 'select',
            required: false,
            options: async () => {
                try {
                    const response = await APIClient.get('facilities');
                    return [
                        { value: '', label: 'No Facility' },
                        ...response.map(facility => ({
                            value: facility.id,
                            label: facility.name
                        }))
                    ];
                } catch (error) {
                    console.error('Error loading facilities:', error);
                    return [{ value: '', label: 'No Facility' }];
                }
            },
            placeholder: 'Select facility'
        },
        {
            name: 'description',
            label: 'Description',
            type: 'textarea',
            required: false,
            placeholder: 'Enter program description',
            rows: 3
        },
        {
            name: 'color',
            label: 'Program Color',
            type: 'color',
            required: false,
            defaultValue: '#3498db'
        },
        {
            name: 'contact_name',
            label: 'Contact Name',
            type: 'text',
            required: false,
            placeholder: 'Enter contact person name'
        },
        {
            name: 'contact_email',
            label: 'Contact Email',
            type: 'email',
            required: false,
            placeholder: 'Enter contact email'
        },
        {
            name: 'contact_phone',
            label: 'Contact Phone',
            type: 'tel',
            required: false,
            placeholder: 'Enter contact phone'
        },
        {
            name: 'status',
            label: 'Status',
            type: 'select',
            required: true,
            options: [
                { value: 'Active', label: 'Active' },
                { value: 'Inactive', label: 'Inactive' }
            ],
            defaultValue: 'Active'
        }
    ],
    
    // Details view configuration
    detailsConfig: {
        title: (data) => `Program: ${data.name}`,
        sections: [
            {
                title: 'Basic Information',
                fields: [
                    { label: 'Name', key: 'name' },
                    { label: 'Type', key: 'program_type_name' },
                    { label: 'Facility', key: 'facility_name' },
                    { label: 'Status', key: 'status', 
                      render: (value) => `<span class="badge ${value === 'Active' ? 'badge-success' : 'badge-secondary'}">${value}</span>` }
                ]
            },
            {
                title: 'Contact Information',
                fields: [
                    { label: 'Contact Name', key: 'contact_name' },
                    { label: 'Contact Email', key: 'contact_email',
                      render: (value) => value ? `<a href="mailto:${value}">${value}</a>` : '' },
                    { label: 'Contact Phone', key: 'contact_phone',
                      render: (value) => value ? `<a href="tel:${value}">${value}</a>` : '' }
                ]
            },
            {
                title: 'Description',
                fields: [
                    { label: 'Description', key: 'description', fullWidth: true }
                ]
            },
            {
                title: 'Statistics',
                fields: [
                    { label: 'Total Episodes', key: 'episode_count' },
                    { label: 'Total Teams', key: 'team_count' },
                    { label: 'Created', key: 'created_at', render: (value) => Utils.formatDateTime(value) },
                    { label: 'Last Updated', key: 'updated_at', render: (value) => Utils.formatDateTime(value) }
                ]
            }
        ]
    },
    
    // API endpoints
    api: {
        list: 'programs_with_stats',
        datatable: 'programs_datatable',
        create: 'create_program',
        update: 'update_program',
        delete: 'delete_program',
        view: 'programs'
    },
    
    // Page-specific event handlers
    handlers: {
        afterInit: () => {
            console.log('Programs page initialized');
        },
        
        beforeCreate: (data) => {
            // Validate program data before sending
            if (!data.name || !data.program_type_id) {
                throw new Error('Program name and type are required');
            }
            return data;
        },
        
        afterCreate: (response) => {
            window.EventBus.emit('programs:refresh');
            return response;
        },
        
        beforeUpdate: (data) => {
            // Validate program data before sending
            if (!data.name || !data.program_type_id) {
                throw new Error('Program name and type are required');
            }
            return data;
        },
        
        afterUpdate: (response) => {
            window.EventBus.emit('programs:refresh');
            return response;
        },
        
        beforeDelete: (id, data) => {
            // Show warning if program has episodes or teams
            const episodeCount = data.episode_count || 0;
            const teamCount = data.team_count || 0;
            
            if (episodeCount > 0 || teamCount > 0) {
                const items = [];
                if (episodeCount > 0) items.push(`${episodeCount} episodes`);
                if (teamCount > 0) items.push(`${teamCount} teams`);
                
                return confirm(`This program has ${items.join(' and ')}. Are you sure you want to delete it?`);
            }
            
            return confirm('Are you sure you want to delete this program?');
        },
        
        afterDelete: () => {
            window.EventBus.emit('programs:refresh');
        },
        
        onView: (id, data) => {
            // Custom view handling if needed
            console.log('Viewing program:', id, data);
        }
    },
    
    // Search and filter options
    searchConfig: {
        placeholder: 'Search programs...',
        fields: ['name', 'program_type_name', 'facility_name', 'contact_name']
    },
    
    // Filter configuration
    filters: {
        defaults: {
            status: '',
            program_type: '',
            facility: ''
        },
        elements: {
            status: 'statusFilter',
            program_type: 'typeFilter', 
            facility: 'facilityFilter'
        }
    },
    
    // DataTable options
    dataTableOptions: {
        order: [[1, 'asc']], // Sort by program name by default
        pageLength: 25,
        responsive: true,
        autoWidth: false,
        columnDefs: [
            { targets: 0, width: '60px' },
            { targets: -1, width: '120px' }
        ]
    },
    
    // Export options
    exportConfig: {
        filename: 'programs-export',
        title: 'Programs',
        columns: [1, 2, 3, 4, 5, 6, 7, 8] // Exclude color and actions columns
    }
};