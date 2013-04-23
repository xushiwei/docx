package main

import (
    "bufio"
    "bytes"
    "fmt"
    "io"
    "log"
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

func process(inFile io.Reader, outFile io.Writer, fn func(string) string) (err error) {

    reader := bufio.NewReader(inFile)
    writer := bufio.NewWriter(outFile)

    defer func() {
        if err == nil {
            err = writer.Flush()
        }
    }()

    for {
        var line string
        if line, err = reader.ReadString('\n'); err != nil {
            if err == io.EOF {
                break // io.EOF isn't really an error
            } else if err != nil {
                return err // finish immediately for real errors
            }
        }

        beautyLine := fn(line)

        if _, err = writer.WriteString(beautyLine); err != nil {
            return err
        }
    }

    return nil
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

func filenamesFromCommandLine() (inFilename, outFilename string, err error) {

    if len(os.Args) > 1 && (os.Args[1] == "-h" || os.Args[1] == "--help") || len(os.Args) < 3 {
        err = fmt.Errorf("Usage: %s <inputFile> <outputFile>", filepath.Base(os.Args[0]))
        return "", "", err
    }

    inFilename, outFilename = os.Args[1], os.Args[2]
    if inFilename == "" {
        log.Fatal("Error : input file can not be empty.")
    }
    if outFilename == "" {
        log.Fatal("Error : output file can not be empty.")
    }

    if inFilename != "" && inFilename == outFilename {
        log.Fatal("Error : won't overwrite the input file.")
    }

    return inFilename, outFilename, nil
}

//-------------------------------------------------------------------------------------------

func main() {

    inFilename, outFilename, err := filenamesFromCommandLine()
    if err != nil {
        fmt.Println(err)
        return
    }

    var (
        inFile  *os.File
        outFile *os.File
    )

    if inFile, err = os.Open(inFilename); err != nil {
        log.Fatal(err)
    }
    defer inFile.Close()

    if outFile, err = os.Create(outFilename); err != nil {
        log.Fatal(err)
    }
    defer outFile.Close()

    if err = process(inFile, outFile, beautify); err != nil {
        log.Fatal(err)
    }
}
