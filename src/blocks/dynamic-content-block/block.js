/**
 * WordPress Block Editor Blocks
 */
( function( blocks, element, components, editor ) {
    var el = element.createElement;
    var SelectControl = components.SelectControl;
    var TextControl = components.TextControl;
    var PanelBody = components.PanelBody;
    var InspectorControls = editor.InspectorControls;

    blocks.registerBlockType('pmp/dynamic-content', {
        title: 'Dynamic Content',
        icon: 'feedback',
        category: 'widgets',
    
        attributes: {
            type: {
                type: 'string',
                default: 'text'
            },
            name: {
                type: 'string',
                default: ''
            },
            title: {
                type: 'string',
                default: ''
            },
            output: {
                type: 'string',
                default: ''
            },
        },
    
        edit: function(props) {
            var attributes = props.attributes;
    
            function updateOutput() {
                const output = `{{{rdynamic_content type='${attributes.type}' name='${attributes.name}' title='${attributes.title}'}}}`;
                props.setAttributes({ output });
            }
    
            function onChangeType(newType) {
                props.setAttributes({ type: newType });
                updateOutput(); // Update output when type changes
            }
    
            function onChangeName(newName) {
                props.setAttributes({ name: newName });
                updateOutput(); // Update output when name changes
            }
    
            function onChangeTitle(newTitle) {
                props.setAttributes({ title: newTitle });
                updateOutput(); // Update output when title changes
            }
    
            // Initialize output if not set
            if (!attributes.output) {
                updateOutput();
            }
            updateOutput();
    
            return [
                el(InspectorControls, { key: 'inspector' },
                    el(PanelBody, { title: 'Dynamic Content Settings', initialOpen: true },
                        el(SelectControl, {
                            label: 'Input Type',
                            value: attributes.type,
                            options: [
                                { label: 'Text', value: 'text' },
                                { label: 'Text Area', value: 'text_area' },
                                { label: 'Number', value: 'number' },
                                { label: 'Email', value: 'email' },
                                { label: 'URL', value: 'url' },
                                { label: 'Select', value: 'select' },
                                { label: 'Radio', value: 'radio' },
                                { label: 'Checkbox', value: 'checkbox' },
                                { label: 'File', value: 'file' }
                            ],
                            onChange: onChangeType
                        }),
                        el(TextControl, {
                            label: 'Field Name',
                            value: attributes.name,
                            onChange: onChangeName
                        }),
                        el(TextControl, {
                            label: 'Field Title',
                            value: attributes.title,
                            onChange: onChangeTitle
                        })
                    )
                ),
                el('div', { className: 'pmp-dynamic-content-block' },
                    el('div', { className: 'pmp-block-header' }, 'Dynamic Content'),
                    el('div', { className: 'pmp-block-content' },
                        el('code', {}, attributes.output) // Display the output here
                    )
                )
            ];
        },
    
        save: function(props) {
            return [];
        }
    });

    // loop content

    blocks.registerBlockType('pmp/loop-content', {
        title: 'Loop Content',
        icon: 'feedback',
        category: 'widgets',
    
        attributes: {
            type: {
                type: 'string',
                default: 'text'
            },
            name: {
                type: 'string',
                default: ''
            },
            title: {
                type: 'string',
                default: ''
            },
            output: {
                type: 'string',
                default: ''
            },
        },
    
        edit: function(props) {
            var attributes = props.attributes;
    
            function updateOutput() {
                const output = `{{{loop_content type='${attributes.type}' name='${attributes.name}' title='${attributes.title}'}}}`;
                props.setAttributes({ output });
            }
    
            function onChangeType(newType) {
                props.setAttributes({ type: newType });
                updateOutput(); // Update output when type changes
            }
    
            function onChangeName(newName) {
                props.setAttributes({ name: newName });
                updateOutput(); // Update output when name changes
            }
    
            function onChangeTitle(newTitle) {
                props.setAttributes({ title: newTitle });
                updateOutput(); // Update output when title changes
            }
    
            // Initialize output if not set
            if (!attributes.output) {
                updateOutput();
            }
            updateOutput();
    
            return [
                el(InspectorControls, { key: 'inspector' },
                    el(PanelBody, { title: 'Loop Content Settings', initialOpen: true },
                        el(SelectControl, {
                            label: 'Input Type',
                            value: attributes.type,
                            options: [
                                { label: 'Text', value: 'text' },
                                { label: 'Text Area', value: 'text_area' },
                                { label: 'Number', value: 'number' },
                                { label: 'Email', value: 'email' },
                                { label: 'URL', value: 'url' },
                                { label: 'Select', value: 'select' },
                                { label: 'Radio', value: 'radio' },
                                { label: 'Checkbox', value: 'checkbox' },
                                { label: 'File', value: 'file' }
                            ],
                            onChange: onChangeType
                        }),
                        el(TextControl, {
                            label: 'Field Name',
                            value: attributes.name,
                            onChange: onChangeName
                        }),
                        el(TextControl, {
                            label: 'Field Title',
                            value: attributes.title,
                            onChange: onChangeTitle
                        })
                    )
                ),
                el('div', { className: 'pmp-dynamic-content-block' },
                    el('div', { className: 'pmp-block-header' }, 'Loop Content'),
                    el('div', { className: 'pmp-block-content' },
                        el('code', {}, attributes.output) // Display the output here
                    )
                )
            ];
        },
    
        save: function(props) {
            return [];
        }
    });

    // Dynamic Loop Block
    blocks.registerBlockType( 'pmp/dynamic-loop', {
        title: 'Dynamic Loop',
        icon: 'update',
        category: 'widgets',
        
        attributes: {
            name: {
                type: 'string',
                default: ''
            },
            loopType: {
                type: 'string',
                default: 'start'
            },
            output: {
                type: 'string',
                default: ''
            },
        },
        
        edit: function( props ) {
            var attributes = props.attributes;
            
            function updateOutput() {
                const output = attributes.loopType === 'start' 
                ? '{{{rdynamic_content_loop_start name=\'' + attributes.name + '\'}}}' 
                : '{{{rdynamic_content_loop_ends name=\'' + attributes.name + '\'}}}';
                props.setAttributes({ output });
            }

            function onChangeName( newName ) {
                props.setAttributes( { name: newName } );
                updateOutput(); // Update output when title changes
            }
            
            function onChangeLoopType( newLoopType ) {
                props.setAttributes( { loopType: newLoopType } );
                updateOutput(); // Update output when title changes
            }
            
            // Initialize output if not set
            if (!attributes.output) {
                updateOutput();
            }

            updateOutput();
            
            return [
                el( InspectorControls, { key: 'inspector' },
                    el( PanelBody, { title: 'Dynamic Loop Settings', initialOpen: true },
                        el( SelectControl, {
                            label: 'Loop Type',
                            value: attributes.loopType,
                            options: [
                                { label: 'Loop Start', value: 'start' },
                                { label: 'Loop End', value: 'end' }
                            ],
                            onChange: onChangeLoopType
                        } ),
                        el( TextControl, {
                            label: 'Loop Name',
                            value: attributes.name,
                            onChange: onChangeName
                        } )
                    )
                ),
                el( 'div', { className: 'pmp-dynamic-loop-block' },
                    el( 'div', { className: 'pmp-block-header' }, 'Dynamic Loop - ' + (attributes.loopType === 'start' ? 'Start' : 'End') ),
                    el( 'div', { className: 'pmp-block-content' },
                        el( 'code', {}, attributes.output )
                    )
                )
            ];
        },
        
        save: function() {
            // Rendering handled by PHP
            return null;
        }
    });

    // Loop Content Block
    blocks.registerBlockType( 'pmp/loop-content', {
        title: 'Loop Content',
        icon: 'editor-ul',
        category: 'widgets',
        
        attributes: {
            type: {
                type: 'string',
                default: 'text'
            },
            name: {
                type: 'string',
                default: ''
            },
            title: {
                type: 'string',
                default: ''
            }
        },
        
        edit: function( props ) {
            var attributes = props.attributes;
            
            function onChangeType( newType ) {
                props.setAttributes( { type: newType } );
            }
            
            function onChangeName( newName ) {
                props.setAttributes( { name: newName } );
            }
            
            function onChangeTitle( newTitle ) {
                props.setAttributes( { title: newTitle } );
            }
            
            return [
                el( InspectorControls, { key: 'inspector' },
                    el( PanelBody, { title: 'Loop Content Settings', initialOpen: true },
                        el( SelectControl, {
                            label: 'Input Type',
                            value: attributes.type,
                            options: [
                                { label: 'Text', value: 'text' },
                                { label: 'Text Area', value: 'text_area' },
                                { label: 'Number', value: 'number' },
                                { label: 'Email', value: 'email' },
                                { label: 'URL', value: 'url' },
                                { label: 'Select', value: 'select' },
                                { label: 'Radio', value: 'radio' },
                                { label: 'Checkbox', value: 'checkbox' },
                                { label: 'File', value: 'file' }
                            ],
                            onChange: onChangeType
                        } ),
                        el( TextControl, {
                            label: 'Field Name',
                            value: attributes.name,
                            onChange: onChangeName
                        } ),
                        el( TextControl, {
                            label: 'Field Title',
                            value: attributes.title,
                            onChange: onChangeTitle
                        } )
                    )
                ),
                el( 'div', { className: 'pmp-loop-content-block' },
                    el( 'div', { className: 'pmp-block-header' }, 'Loop Content' ),
                    el( 'div', { className: 'pmp-block-content' },
                        el( 'code', {}, '{{{loop_content type=\'' + attributes.type + '\' name=\'' + attributes.name + '\' title=\'' + attributes.title + '\'}}}' )
                    )
                )
            ];
        },
        
        save: function() {
            // Rendering handled by PHP
            return null;
        }
    });
} )( 
    window.wp.blocks,
    window.wp.element,
    window.wp.components,
    window.wp.editor
);