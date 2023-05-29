import sys
# Define the file path and variable to search for

file_path = '/etc/ansible/hosts'
search_var = sys.argv[1]

# Open the file and search for the variable

with open(file_path, 'r') as file:
    contents = file.read()
    if search_var in contents:
        print('1')
    else:
        print('0')
