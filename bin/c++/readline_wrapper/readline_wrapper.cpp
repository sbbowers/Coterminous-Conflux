// c,read.cpp
//   Used to provide readline support for systems that don't provide the readline php extension
//
// Usage: c,read [<question>]
//      requires tty on stdin, stdout
//      returns commands on stderr
//      command history read from file descriptor 3, if available
//      command history read from file descriptor 4, if available
//
// Compile with: `g++ -o c,read c,read.cpp -static-libgcc -static-libstdc++ -lreadline -lncurses -ldl -static`
// Or: `g++ -o c,read c,read.cpp -lreadline`
//
#include <stdio.h>
#include <stdlib.h>
#include <readline/readline.h>
#include <readline/history.h>
#include <string>
#include <signal.h>

int sig_read = 0;
std::string prompt;
char * command;
const char * history_file = "/home/sbbowers/.coterminousconflux/console_history";
char tbuffer[8192];

void load_history();
void load_tab_completion();
void read_command(FILE * fd_sig, FILE * fd_out);
void sighandler(int sig);


int main(int argc, char *argv[])
{
    read_history(history_file);
    load_tab_completion();
    signal(SIGINT, &sighandler); // Don't die on sigint

    FILE * fd_sig = fdopen(5, "r"); // get signals to read from fd5
    if(!fd_sig)
        exit(0);

    FILE * fd_out = fdopen(6, "w"); // output lines on fd6
    if(!fd_out)
        exit(0);

    if(argc > 1) // load the prompt if available
        prompt = argv[1];

    do 
    {
        read_command(fd_sig, fd_out);
    } while(command);
 
    free(command);
    command = NULL;
    fclose(fd_sig);
    fclose(fd_out);

    write_history(history_file);
    return 0;
}

// Read input from stdin and output to fd_out
// Waits for a line of input as a signal on fd_sig before reading
// signal used for i/o mutex because this application is designed to share stdout
void read_command(FILE * fd_sig, FILE * fd_out)
{

    if(!sig_read)
    {
       fgets(tbuffer, 8192, fd_sig); // wait for signal from main process
       tbuffer[strcspn(tbuffer, "\n")] = '\0'; // remove newline
       prompt = tbuffer;
       sig_read = 1; 
    }

    if(ferror(fd_sig) || feof(fd_sig))
    {
        free(command);
        command = NULL;
    }
    else
    {
        command = readline(prompt.c_str());
        if(command)
        {
            if(fd_out)
            {
                fprintf(fd_out, "%s\n", command);
                fflush(fd_out);
                sig_read = 0;
            }
            else
            {
                free(command);
                command = NULL;
            }

            if(command[0])
                add_history(command);
        }
    }
}

void load_tab_completion()
{
    /*
    FILE * fd;
    char t[1024];
    if(fd = fdopen(4, "r"))
    {
        while(fgets(t, 1000, fd))
        {
            t[strcspn(t, "\n")] = '\0'; // remove newline
            printf("fd1: %s", t);
        }
    }   
    */  
}

// Prevent SIGINT and cleans up the terminal
void sighandler(int sig) 
{
    printf("\n%s", prompt.c_str());
}