
import os
import shutil

def copy_php_to_txt(source_dir='.', output_folder='php_txt_copies'):
    """
    Copy all .php files as .txt files into a specified folder

    Args:
        source_dir: Directory to search for PHP files (default: current directory)
        output_folder: Folder name to store the .txt copies
    """

    # Create output folder if it doesn't exist
    if not os.path.exists(output_folder):
        os.makedirs(output_folder)
        print(f"Created folder: {output_folder}")

    # Counter for copied files
    copied_count = 0

    # Walk through all directories and subdirectories
    for root, dirs, files in os.walk(source_dir):
        # Skip the output folder itself
        if output_folder in root:
            continue

        for file in files:
            if file.endswith('.php'):
                # Get the full path of the source file
                source_path = os.path.join(root, file)

                # Create relative path structure in output folder
                relative_path = os.path.relpath(root, source_dir)
                if relative_path == '.':
                    dest_dir = output_folder
                else:
                    dest_dir = os.path.join(output_folder, relative_path)

                # Create subdirectories in output folder if needed
                if not os.path.exists(dest_dir):
                    os.makedirs(dest_dir)

                # Change extension from .php to .txt
                txt_filename = file[:-4] + '.txt'
                dest_path = os.path.join(dest_dir, txt_filename)

                # Copy the file
                shutil.copy2(source_path, dest_path)
                copied_count += 1
                print(f"Copied: {source_path} -> {dest_path}")

    print(f"\nTotal files copied: {copied_count}")
    return copied_count

if __name__ == "__main__":
    # Run the function
    # You can customize these parameters:
    # - source_dir: where to look for PHP files (default: current directory
   copy_php_to_txt(source_dir='.', output_folder='php_txt_copies')