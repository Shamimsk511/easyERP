import os

def combine_php_to_single_file(source_dir='.', output_file='all_php_files.txt'):
    """
    Combine all .php files into a single .txt file with filenames as titles

    Args:
        source_dir: Directory to search for PHP files (default: current directory)
        output_file: Output filename (default: 'all_php_files.txt')
    """

    # Counter for processed files
    file_count = 0

    # Open the output file for writing
    with open(output_file, 'w', encoding='utf-8') as outfile:
        # Walk through all directories and subdirectories
        for root, dirs, files in os.walk(source_dir):
            for file in files:
                if file.endswith('.php'):
                    # Get the full path of the source file
                    source_path = os.path.join(root, file)

                    # Get relative path for better readability
                    relative_path = os.path.relpath(source_path, source_dir)

                    # Write separator and filename as title
                    outfile.write('=' * 80 + '\n')
                    outfile.write(f'FILE: {relative_path}\n')
                    outfile.write('=' * 80 + '\n\n')

                    # Read and write the PHP file content
                    try:
                        with open(source_path, 'r', encoding='utf-8') as infile:
                            content = infile.read()
                            outfile.write(content)
                    except Exception as e:
                        outfile.write(f'[Error reading file: {e}]\n')

                    # Add spacing between files
                    outfile.write('\n\n\n')

                    file_count += 1
                    print(f"Added: {relative_path}")

    print(f"\n✓ Combined {file_count} PHP files into: {output_file}")
    return file_count

if __name__ == "__main__":
    # Run the function
    combine_php_to_single_file(source_dir='.', output_file='all_php_files.txt')