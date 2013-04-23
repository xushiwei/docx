package main

import (
    "bufio"
    "bytes"
    "fmt"
    "io"
    "log"
    "errors"
    "os"
    "path/filepath"
    "unicode"
    "unicode/utf8"
)

var punctuationList = map[string]bool{
    "。": true,
    "！": true,
    "？": true,
    "，": true,
    "；": true,
    "：": true,
    "、": true,

    "“":  true,
    "”":  true,
    "（":  true,
    "）":  true,
    "【":  true,
    "】":  true,
    "『":  true,
    "』":  true,
    "——": true,
    "《":  true,
    "》":  true,
}

var insertionChar = " "

// -----------------------------------------------------------------------------------------

func isChinese(r rune) bool {

    return utf8.RuneLen(r) == 3
}

func isEnglish(r rune) bool {

    return utf8.RuneLen(r) == 1
}

// To test whether the specified s is a chinese punctuation or not.
func isPunctuation(s string) bool {

    return punctuationList[s]
}

// -----------------------------------------------------------------------------------------

func process(inFile io.Reader, fn func(string) string) (outBuf *bytes.Buffer, err error) {

    outBuf = new(bytes.Buffer)
    reader := bufio.NewReader(inFile)

    for {
        var line string
        if line, err = reader.ReadString('\n'); err != nil {
            if err == io.EOF {
                break // io.EOF isn't really an error
            } else if err != nil {
                return outBuf, err // finish immediately for real errors
            }
        }

        beautyLine := fn(line)


        if _, err = outBuf.WriteString(beautyLine); err != nil {
            return outBuf, err
        }
    }

    return outBuf, nil
}

func beautify(line string) string {

    buf := new(bytes.Buffer)
    lineRune := []rune(line)

    for i, current := range lineRune {
        if i == 0 {
            buf.WriteString(string(current))
            continue
        }
        previous := lineRune[i-1]

        // chinese english char appears alternatively, when english char is not a space and
        // chinese char is not a punctuation, insert a whitespace.
        if isEnglish(previous) && isChinese(current) {
            if !unicode.IsSpace(previous) && !isPunctuation(string(current)) {
                buf.WriteString(insertionChar)
            }
        } else if isChinese(previous) && isEnglish(current) {
            if !isPunctuation(string(previous)) && !unicode.IsSpace(current) {
                buf.WriteString(insertionChar)
            }
        }

        buf.WriteString(string(current))
    }

    return buf.String()
}

func save(out *os.File, result string) (err error) {

    _, err = out.WriteString(result)
    return err 
}

func filenamesFromCommandLine() (inFilename string, err error) {

    if len(os.Args) > 1 && (os.Args[1] == "-h" || os.Args[1] == "--help") || len(os.Args) < 2 {
        err = fmt.Errorf("Usage: %s <FileName>", filepath.Base(os.Args[0]))
        return "", err
    }

    inFilename = os.Args[1]
    if inFilename == "" {
        err = errors.New("Error : input file can not be empty.")
        return "", err
    }

    return inFilename, nil
}

//-------------------------------------------------------------------------------------------

func main() {

    inFilename, err := filenamesFromCommandLine()
    // overwrite the original file
    outFilename := inFilename

    if err != nil {
        fmt.Println(err)
        return
    }

    var (
        inFile  *os.File
        outFile *os.File
    )

    if inFile, err = os.Open(inFilename); err != nil {
        log.Fatal("Fail to open input file, ", err)
    }
    defer inFile.Close()

    result, err := process(inFile, beautify)
    if err != nil {
        log.Fatal("Fail to process the input file, ", err)
    }

    if outFile, err = os.Create(outFilename); err != nil {
        log.Fatal("Fail to create the output file, ", err)
    }
    defer outFile.Close()

    if err = save(outFile, result.String()); err != nil {
        log.Fatal("Fail to save result, ", err)
    }
    
}
